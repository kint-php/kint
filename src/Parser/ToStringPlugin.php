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

use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
use ReflectionClass;
use SimpleXMLElement;
use SplFileObject;
use Throwable;

class ToStringPlugin extends AbstractPlugin
{
    public static array $blacklist = [
        SimpleXMLElement::class,
        SplFileObject::class,
    ];

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
        $reflection = new ReflectionClass($var);
        if (!$reflection->hasMethod('__toString')) {
            return;
        }

        foreach (self::$blacklist as $class) {
            if ($var instanceof $class) {
                return;
            }
        }

        try {
            $string = (string) $var;
        } catch (Throwable $t) {
            return;
        }

        $base_obj = new Value($o->name);
        $base_obj->depth = $o->depth + 1;
        if (null !== $o->access_path) {
            $base_obj->access_path = '(string) '.$o->access_path;
        }

        $r = new Representation('toString');
        $r->contents = $this->getParser()->parse($string, $base_obj);

        $o->addRepresentation($r);
    }
}
