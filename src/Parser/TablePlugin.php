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

// Note: Interaction with ArrayLimitPlugin:
// Any array limited children will be shown in tables identically to
// non-array-limited children since the table only shows that it is an array
// and it's size anyway. Because ArrayLimitPlugin halts the parse on finding
// a limit all other plugins including this one are stopped, so you cannot get
// a tabular representation of an array that is longer than the limit.
class TablePlugin extends AbstractPlugin
{
    public static int $max_width = 300;
    public static int $min_width = 2;

    public function getTypes(): array
    {
        return ['array'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (!\is_array($o->value->contents ?? null)) {
            return;
        }

        $array = $this->getParser()->getCleanArray($var);

        if (\count($array) < 2) {
            return;
        }

        // Ensure this is an array of arrays and that all child arrays have the
        // same keys. We don't care about their children - if there's another
        // "table" inside we'll just make another one down the value tab
        $keys = null;
        foreach ($array as $elem) {
            if (!\is_array($elem)) {
                return;
            }

            if (null === $keys) {
                if (\count($elem) < self::$min_width || \count($elem) > self::$max_width) {
                    return;
                }

                $keys = \array_keys($elem);
            } elseif (\array_keys($elem) !== $keys) {
                return;
            }
        }

        // Ensure none of the child arrays are recursion or depth limit. We
        // don't care if their children are since they are the table cells
        /**
         * @psalm-suppress PossiblyNullIterator
         * Psalm bug #11055
         */
        foreach ($o->value->contents as $childarray) {
            if (empty($childarray->value->contents)) {
                return;
            }
        }

        // Objects by reference for the win! We can do a copy-paste of the value
        // representation contents and just slap a new hint on there and hey
        // presto we have our table representation with no extra memory used!
        $table = new Representation('Table');
        $table->contents = $o->value->contents;
        $table->hints['table'] = true;
        $o->addRepresentation($table, 0);
    }
}
