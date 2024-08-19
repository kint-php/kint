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
use Kint\Zval\MethodValue;
use Kint\Zval\Representation\MethodDefinitionRepresentation;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
use ReflectionClass;

/**
 * @psalm-type OwnedMethodValue = MethodValue&object{owner_class: class-string}
 */
class ClassMethodsPlugin extends AbstractPlugin
{
    /** @psalm-var array<class-string, list<OwnedMethodValue>> */
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

        $class = \get_class($var);

        // assuming class definition will not change inside one request
        if (!isset(self::$cache[$class])) {
            $methods = [];

            $reflection = new ReflectionClass($class);

            foreach ($reflection->getMethods() as $method) {
                /** @psalm-var OwnedMethodValue */
                $methods[] = new MethodValue($method);
            }

            /**
             * @psalm-suppress InvalidArgument
             * Appears to have been fixed in master
             */
            \usort($methods, [self::class, 'sort']);

            self::$cache[$class] = $methods;
        }

        if (!empty(self::$cache[$class])) {
            $rep = new Representation('Available methods', 'methods');
            $rep->contents = [];

            $parser = $this->getParser();

            // Can't cache access paths
            foreach (self::$cache[$class] as $m) {
                $method = clone $m;
                $method->depth = $o->depth + 1;

                if (!$parser->childHasPath($o, $method)) {
                    $method->access_path = null;
                } else {
                    $method->setAccessPathFrom($o);
                }

                if ($method->owner_class !== $class && ($d = $method->getRepresentation('method_definition')) instanceof MethodDefinitionRepresentation) {
                    $d = clone $d;
                    $d->inherited = true;
                    $method->replaceRepresentation($d);
                }

                $rep->contents[] = $method;
            }

            $o->addRepresentation($rep);
        }
    }

    /**
     * @psalm-param OwnedMethodValue $a
     * @psalm-param OwnedMethodValue $b
     */
    private static function sort(MethodValue $a, MethodValue $b): int
    {
        $sort = ((int) $a->static) - ((int) $b->static);
        if ($sort) {
            return $sort;
        }

        $sort = Value::sortByAccess($a, $b);
        if ($sort) {
            return $sort;
        }

        /**
         * @psalm-suppress PossiblyNullArgument
         * Psalm bug #11055
         */
        $sort = InstanceValue::sortByHierarchy($a->owner_class, $b->owner_class);
        if ($sort) {
            return $sort;
        }

        return $a->startline - $b->startline;
    }
}
