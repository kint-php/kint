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

use Kint\Zval\Context\ClassConstContext;
use Kint\Zval\Context\ClassDeclaredContext;
use Kint\Zval\Context\ClassOwnedContext;
use Kint\Zval\Context\StaticPropertyContext;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\UninitializedValue;
use Kint\Zval\Value;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionProperty;
use UnitEnum;

class ClassStaticsPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    /** @psalm-var array<class-string, array<1|0, list<Value>>> */
    private static array $cache = [];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, Value $v, int $trigger): Value
    {
        if (!$v instanceof InstanceValue) {
            return $v;
        }

        $class = $v->classname;
        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();
        $r = new ReflectionClass($class);

        $crep = new Representation('Class constants', 'constants');
        $crep->contents = $this->getCachedConstants($r);

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

        $srep = new Representation('Static properties', 'statics');
        $srep->contents = [];

        $found_statics = [];

        $cdepth = $v->getContext()->getDepth();

        foreach ($statics as $static) {
            $prop = new StaticPropertyContext(
                '$'.$static->getName(),
                $static->getDeclaringClass()->name,
                ClassDeclaredContext::ACCESS_PUBLIC
            );
            $prop->depth = $cdepth + 1;
            $prop->final = KINT_PHP84 && $static->isFinal();

            if ($static->isProtected()) {
                $prop->access = ClassDeclaredContext::ACCESS_PROTECTED;
            } elseif ($static->isPrivate()) {
                $prop->access = ClassDeclaredContext::ACCESS_PRIVATE;
            }

            if ($prop->isAccessible($parser->getCallerClass())) {
                $prop->access_path = '\\'.$prop->owner_class.'::'.$prop->name;
            }

            if (isset($found_statics[$prop->name])) {
                $statics_full_name = true;
            } else {
                $found_statics[$prop->name] = true;

                if ($prop->owner_class !== $class && ClassDeclaredContext::ACCESS_PRIVATE === $prop->access) {
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
                $srep->contents[] = new UninitializedValue($prop);
            } else {
                $static = $static->getValue();
                $srep->contents[] = $parser->parse($static, $prop);
            }
        }

        if (\count($srep->contents)) {
            $v->addRepresentation($srep);
        }

        if (\count($crep->contents)) {
            $v->addRepresentation($crep);
        }

        return $v;
    }

    /** @psalm-return list<Value> */
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
                    $c = $value->getContext();
                    $cname = $c->getName();

                    if (isset($reflectors[$cname]) && $c instanceof ClassOwnedContext && $reflectors[$cname][0]->getDeclaringClass()->name === $c->owner_class) {
                        $consts[$cname] = $value;
                        unset($reflectors[$cname]);
                    } else {
                        $value = clone $value;
                        $c = $value->getContext();
                        if ($c instanceof ClassOwnedContext) {
                            $c->name = $c->owner_class.'::'.$cname;
                        }
                        $consts[] = $value;
                    }
                }
            }

            foreach ($reflectors as [$cr, $val]) {
                $context = new ClassConstContext(
                    $cr->name,
                    $cr->getDeclaringClass()->name,
                    ClassDeclaredContext::ACCESS_PUBLIC
                );
                $context->depth = $pdepth ?: 1;
                $context->final = KINT_PHP81 && $cr->isFinal();

                if ($cr->isProtected()) {
                    $context->access = ClassDeclaredContext::ACCESS_PROTECTED;
                } elseif ($cr->isPrivate()) {
                    $context->access = ClassDeclaredContext::ACCESS_PRIVATE;
                } else {
                    // No access path for protected/private. Tough shit the cache is worth it
                    $context->access_path = '\\'.$context->owner_class.'::'.$context->name;
                }

                $consts[$cr->name] = $parser->parse($val, $context);
            }

            /** @psalm-var Value[] $consts */
            self::$cache[$class][$pdepth_enabled] = \array_values($consts);
        }

        return self::$cache[$class][$pdepth_enabled];
    }
}
