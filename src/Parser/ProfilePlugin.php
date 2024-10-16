<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jonathan Vollebregt (jnvsor@gmail.com), Rokas Šleinius (raveren@gmail.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Kint\Parser;

use Kint\Zval\Context\BaseContext;
use Kint\Zval\Context\ContextInterface;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\ProfileRepresentation;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

class ProfilePlugin extends AbstractPlugin implements PluginBeginInterface, PluginCompleteInterface
{
    public static bool $profile_value = false;

    protected array $instance_counts = [];
    protected array $instance_complexity = [];
    protected array $instance_count_stack = [];
    protected array $class_complexity = [];
    protected array $class_count_stack = [];

    public function getTypes(): array
    {
        return ['string', 'object', 'array', 'integer', 'double', 'resource'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_BEGIN | Parser::TRIGGER_COMPLETE;
    }

    public function parseBegin(&$var, ContextInterface $c): ?Value
    {
        if (0 === $c->getDepth()) {
            $this->instance_counts = [];
            $this->instance_complexity = [];
            $this->instance_count_stack = [];
            $this->class_complexity = [];
            $this->class_count_stack = [];
        }

        if (\is_object($var)) {
            $hash = \spl_object_hash($var);
            $this->instance_counts[$hash] ??= 0;
            $this->instance_complexity[$hash] ??= 0;
            $this->instance_count_stack[$hash] ??= 0;

            if (0 === $this->instance_count_stack[$hash]) {
                foreach (\class_parents($var) as $class) {
                    $this->class_count_stack[$class] ??= 0;
                    ++$this->class_count_stack[$class];
                }

                foreach (\class_implements($var) as $iface) {
                    $this->class_count_stack[$iface] ??= 0;
                    ++$this->class_count_stack[$iface];
                }
            }

            ++$this->instance_count_stack[$hash];
        }

        return null;
    }

    public function parseComplete(&$var, Value $v, int $trigger): Value
    {
        if ($v instanceof InstanceValue) {
            --$this->instance_count_stack[$v->spl_object_hash];

            if (0 === $this->instance_count_stack[$v->spl_object_hash]) {
                foreach (\class_parents($var) as $class) {
                    --$this->class_count_stack[$class];
                }

                foreach (\class_implements($var) as $iface) {
                    --$this->class_count_stack[$iface];
                }
            }
        }

        // Don't check subs if we're in recursion or array limit
        if (~$trigger & Parser::TRIGGER_SUCCESS) {
            return $v;
        }

        $sub_complexity = 1;
        $value_included = false;

        foreach ($v->getRepresentations() as $rep) {
            if ($v->value === $rep) {
                $value_included = true;
            }

            if ($rep->contents instanceof Value) {
                $profile = $rep->contents->getRepresentation('profiling');
                $sub_complexity += $profile instanceof ProfileRepresentation ? $profile->complexity : 1;
            } elseif (\is_array($rep->contents)) {
                foreach ($rep->contents as $value) {
                    $profile = $value->getRepresentation('profiling');
                    $sub_complexity += $profile instanceof ProfileRepresentation ? $profile->complexity : 1;
                }
            }
        }

        if (!$value_included && self::$profile_value && $v->value) {
            if ($v->value->contents instanceof Value) {
                $profile = $v->value->contents->getRepresentation('profiling');
                $sub_complexity += $profile instanceof ProfileRepresentation ? $profile->complexity : 1;
            } elseif (\is_array($v->value->contents)) {
                foreach ($v->value->contents as $value) {
                    $profile = $value->getRepresentation('profiling');
                    $sub_complexity += $profile instanceof ProfileRepresentation ? $profile->complexity : 1;
                }
            }
        }

        if ($v instanceof InstanceValue) {
            ++$this->instance_counts[$v->spl_object_hash];
            if (0 === $this->instance_count_stack[$v->spl_object_hash]) {
                $this->instance_complexity[$v->spl_object_hash] += $sub_complexity;

                $this->class_complexity[$v->classname] ??= 0;
                $this->class_complexity[$v->classname] += $sub_complexity;

                foreach (\class_parents($var) as $class) {
                    $this->class_complexity[$class] ??= 0;
                    if (0 === $this->class_count_stack[$class]) {
                        $this->class_complexity[$class] += $sub_complexity;
                    }
                }

                foreach (\class_implements($var) as $iface) {
                    $this->class_complexity[$iface] ??= 0;
                    if (0 === $this->class_count_stack[$iface]) {
                        $this->class_complexity[$iface] += $sub_complexity;
                    }
                }
            }
        }

        if (0 === $v->getContext()->getDepth()) {
            $rep = new Representation('Class complexity');
            $rep->contents = [];

            \arsort($this->class_complexity);

            foreach ($this->class_complexity as $name => $complexity) {
                $class = new Value(new BaseContext($name));
                $class->type = 'integer';
                $class->value = new Representation('Classes');
                $class->value->contents = $complexity;

                $rep->contents[] = $class;
            }

            $v->addRepresentation($rep, 0);
        }

        $rep = new ProfileRepresentation($sub_complexity);
        /** @psalm-suppress UnsupportedPropertyReferenceUsage */
        if ($v instanceof InstanceValue) {
            $rep->instance_counts = &$this->instance_counts[$v->spl_object_hash];
            $rep->instance_complexity = &$this->instance_complexity[$v->spl_object_hash];
        }

        $v->addRepresentation($rep, 0);

        return $v;
    }
}
