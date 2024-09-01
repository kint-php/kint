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

use InvalidArgumentException;
use Kint\Utils;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

class ArrayLimitPlugin extends AbstractPlugin
{
    /**
     * Maximum size of arrays before limiting.
     */
    public static int $trigger = 1000;

    /**
     * Maximum amount of items to show in a limited array.
     */
    public static int $limit = 50;

    /**
     * Don't limit arrays with string keys.
     */
    public static bool $numeric_only = true;

    public function getTypes(): array
    {
        return ['array'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_BEGIN;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (self::$limit >= self::$trigger) {
            throw new InvalidArgumentException('ArrayLimitPlugin::$limit can not be lower than ArrayLimitPlugin::$trigger');
        }

        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();

        if (!$pdepth) {
            return;
        }

        if ($o->depth >= $pdepth - 1) {
            return;
        }

        if (\count($var) < self::$trigger) {
            return;
        }

        if (self::$numeric_only && Utils::isAssoc($var)) {
            return;
        }

        $base = clone $o;
        $base->depth = $pdepth - 1;
        $p2 = \array_slice($var, self::$limit, null, true);
        $obj = $parser->parse($p2, $base);
        $obj->size = \count($var);

        if ('array' != $obj->type || !\is_array($obj->value->contents ?? null)) {
            return; // @codeCoverageIgnore
        }

        /**
         * @psalm-var object{contents: array}&Representation $obj->value
         * Psalm bug #11055
         */
        $obj->depth = $o->depth;
        foreach ($obj->value->contents as $child) {
            $this->replaceDepthLimit($child, $o->depth + 1);
        }

        $p1 = \array_slice($var, 0, self::$limit, true);
        $base = clone $o;

        /**
         * @psalm-var object{contents: array}&Representation $slice->value
         * Psalm bug #11055
         */
        $slice = $parser->parse($p1, $base);
        $obj->value->contents = \array_merge($slice->value->contents, $obj->value->contents);

        $o = $obj;

        $parser->haltParse();
    }

    protected function replaceDepthLimit(Value $o, int $depth): void
    {
        $o->depth = $depth;

        $pdepth = $this->getParser()->getDepthLimit();

        if (isset($o->hints['depth_limit']) && $pdepth && $o->depth < $pdepth) {
            unset($o->hints['depth_limit']);
            $o->hints['array_limit'] = true;
        }

        $reps = $o->getRepresentations();
        if ($o->value && !\in_array($o->value, $reps, true)) {
            $reps[] = $o->value;
        }

        foreach ($reps as $rep) {
            if ($rep->contents instanceof Value) {
                $this->replaceDepthLimit($rep->contents, $depth + 1);
            } elseif (\is_array($rep->contents)) {
                foreach ($rep->contents as $child) {
                    $this->replaceDepthLimit($child, $depth + 1);
                }
            }
        }
    }
}
