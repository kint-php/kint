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

use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionProperty;
use UnitEnum;

/**
 * @psalm-type OwnedValue = Value&object{owner_class: class-string}
 */
class ClassStaticsPlugin extends AbstractPlugin
{
    /** @psalm-var array<class-string, array<1|0, list<OwnedValue>>> */
    private static array $cache = [];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (!$o instanceof InstanceValue) {
            return;
        }

        $class = $o->classname;
        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();
        $r = new ReflectionClass($class);

        $rep = new Representation('Static class properties', 'statics');
        $rep->contents = [];

        $found_consts = [];
        $consts_full_name = false;
        $consts = $this->getCachedConstants($r);
        foreach ($consts as $const) {
            $const = clone $const;
            $const->depth = $o->depth + 1;
            if ($parser->childHasPath($o, $const)) {
                $const->access_path = '\\'.$const->owner_class.'::'.$const->name;
            }

            if (isset($found_consts[$const->name])) {
                $consts_full_name = true;
            } else {
                $found_consts[$const->name] = true;

                if ($const->owner_class !== $class && Value::ACCESS_PRIVATE === $const->access) {
                    $consts_full_name = true;
                }
            }

            if ($consts_full_name) {
                $const->name = $const->owner_class.'::'.$const->name;
            }

            $rep->contents[] = $const;
        }

        $statics_full_name = false;
        $statics = [];
        $props = $r->getProperties(ReflectionProperty::IS_STATIC);
        foreach ($props as $prop) {
            $statics[$prop->name] = $prop;
        }

        while ($r = $r->getParentClass()) {
            foreach ($r->getProperties(ReflectionProperty::IS_STATIC) as $static) {
                if (isset($statics[$static->name]) && $statics[$static->name]->getDeclaringClass()->name === $static->getDeclaringClass()->name) {
                    continue;
                }
                $statics[] = $static;
            }
        }

        $found_statics = [];

        foreach ($statics as $static) {
            $prop = new Value('$'.$static->getName());
            $prop->depth = $o->depth + 1;
            $prop->static = true;
            $prop->operator = Value::OPERATOR_STATIC;
            $prop->owner_class = $static->getDeclaringClass()->name;

            $prop->access = Value::ACCESS_PUBLIC;
            if ($static->isProtected()) {
                $prop->access = Value::ACCESS_PROTECTED;
            } elseif ($static->isPrivate()) {
                $prop->access = Value::ACCESS_PRIVATE;
            }

            if ($parser->childHasPath($o, $prop)) {
                $prop->access_path = '\\'.$prop->owner_class.'::'.$prop->name;
            }

            if (isset($found_statics[$prop->name])) {
                $statics_full_name = true;
            } else {
                $found_statics[$prop->name] = true;

                if ($prop->owner_class !== $class && Value::ACCESS_PRIVATE === $prop->access) {
                    $statics_full_name = true;
                }
            }

            if ($statics_full_name) {
                $prop->name = $prop->owner_class.'::'.$prop->name;
            }

            $static->setAccessible(true);

            /**
             * @psalm-suppress TooFewArguments
             * Appears to have been fixed in master
             */
            if (!$static->isInitialized()) {
                $prop->type = 'uninitialized';
                $rep->contents[] = $prop;
            } else {
                $static = $static->getValue();
                $rep->contents[] = $parser->parse($static, $prop);
            }
        }

        if (empty($rep->contents)) {
            return;
        }

        $o->addRepresentation($rep);
    }

    /** @psalm-return list<OwnedValue> */
    private function getCachedConstants(ReflectionClass $r): array
    {
        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();
        $pdepth_enabled = (int) ($pdepth > 0);
        $class = $r->getName();

        // Separate cache for dumping with/without depth limit
        // This means we can do immediate depth limit on normal dumps
        if (!isset(self::$cache[$class][$pdepth_enabled])) {
            $consts = [];
            $reflectors = [];

            foreach ($r->getConstants() as $name => $val) {
                $cr = new ReflectionClassConstant($class, $name);

                // Skip enum constants
                if (\is_a($cr->class, UnitEnum::class, true) && $val instanceof UnitEnum && $cr->class === \get_class($val)) {
                    continue;
                }

                $reflectors[$cr->name] = [$cr, $val];
                $consts[$cr->name] = null;
            }

            if ($r = $r->getParentClass()) {
                $parents = $this->getCachedConstants($r);

                foreach ($parents as $value) {
                    if (isset($reflectors[$value->name]) && $reflectors[$value->name][0]->getDeclaringClass()->name === $value->owner_class) {
                        $consts[$value->name] = $value;
                    } else {
                        $consts[] = clone $value;
                    }
                }
            }

            foreach ($reflectors as [$cr, $val]) {
                $const = new Value($cr->name);
                $const->const = true;
                $const->depth = $pdepth ?: 1;
                $const->owner_class = $cr->getDeclaringClass()->name;
                $const->operator = Value::OPERATOR_STATIC;

                $const->access = Value::ACCESS_PUBLIC;
                if ($cr->isProtected()) {
                    $const->access = Value::ACCESS_PROTECTED;
                } elseif ($cr->isPrivate()) {
                    $const->access = Value::ACCESS_PRIVATE;
                }

                // No access path for deeper values. Tough shit the cache is worth it
                $consts[$cr->name] = $parser->parse($val, $const);
            }

            /** @psalm-var list<OwnedValue> */
            self::$cache[$class][$pdepth_enabled] = \array_values($consts);
        }

        return self::$cache[$class][$pdepth_enabled];
    }
}
