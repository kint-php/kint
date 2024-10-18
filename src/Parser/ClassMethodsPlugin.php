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

use Kint\Value\AbstractValue;
use Kint\Value\Context\MethodContext;
use Kint\Value\InstanceValue;
use Kint\Value\MethodValue;
use Kint\Value\Representation\CallableDefinitionRepresentation;
use Kint\Value\Representation\Representation;
use ReflectionClass;

/**
 * @psalm-type OwnedMethodValue = MethodValue&object{context: MethodContext}
 */
class ClassMethodsPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    /** @psalm-var array<class-string, OwnedMethodValue[]> */
    private static array $cache = [];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    /**
     * @psalm-template T of AbstractValue
     *
     * @psalm-param mixed $var
     * @psalm-param T $v
     *
     * @psalm-return T
     */
    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (!$v instanceof InstanceValue) {
            return $v;
        }

        $class = $v->getClassName();

        // assuming class definition will not change inside one request
        if (!isset(self::$cache[$class])) {
            $methods = [];

            $r = new ReflectionClass($class);

            foreach ($r->getMethods() as $mr) {
                if (!KINT_PHP80 && $mr->isPrivate() && $mr->getDeclaringClass()->name !== $r->getName()) {
                    continue; // @codeCoverageIgnore
                }

                /** @psalm-var OwnedMethodValue $method */
                $method = new MethodValue($mr);
                $methods[$method->getContext()->getName()] = $method;
            }

            while ($r = $r->getParentClass()) {
                foreach ($r->getMethods() as $mr) {
                    if (!$mr->isPrivate() && !$mr->isStatic()) {
                        continue;
                    }

                    if (!KINT_PHP80 && $mr->isPrivate() && $mr->getDeclaringClass()->name !== $r->getName()) {
                        continue; // @codeCoverageIgnore
                    }

                    if (isset($methods[$mr->name])) {
                        $c = $methods[$mr->name]->getContext();
                        if ($c->owner_class === $mr->getDeclaringClass()->name) {
                            continue;
                        }
                    }

                    /** @psalm-var OwnedMethodValue $method */
                    $method = new MethodValue($mr);
                    $methods[] = $method;
                }
            }

            self::$cache[$class] = $methods;
        }

        if (!empty(self::$cache[$class])) {
            $rep = new Representation('Available methods', 'methods');
            $rep->contents = [];

            $cdepth = $v->getContext()->getDepth();
            $parser = $this->getParser();

            // Can't cache access paths or depth
            foreach (self::$cache[$class] as $key => $m) {
                $method = clone $m;
                $mc = $method->getContext();

                $mc->depth = $cdepth + 1;

                if ($mc->isAccessible($parser->getCallerClass())) {
                    $mc->setAccessPathFromParent($v);
                }

                if ($mc->owner_class !== $class && ($d = $method->getRepresentation('callable_definition')) instanceof CallableDefinitionRepresentation) {
                    $d = clone $d;
                    $d->inherited = true;
                    $method->replaceRepresentation($d);
                }

                if ($key !== $mc->name) {
                    $mc->name = $mc->owner_class.'::'.$mc->name;
                }

                $rep->contents[] = $method;
            }

            $v->addRepresentation($rep);
        }

        return $v;
    }
}
