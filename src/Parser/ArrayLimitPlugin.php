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
use Kint\Zval\Context\BaseContext;
use Kint\Zval\Context\ContextInterface;
use Kint\Zval\Representation\ProfileRepresentation;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

class ArrayLimitPlugin extends AbstractPlugin implements PluginBeginInterface
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

    public function parseBegin(&$var, ContextInterface $c): ?Value
    {
        if (self::$limit >= self::$trigger) {
            throw new InvalidArgumentException('ArrayLimitPlugin::$limit can not be lower than ArrayLimitPlugin::$trigger');
        }

        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();

        if (!$pdepth) {
            return null;
        }

        $cdepth = $c->getDepth();

        if ($cdepth >= $pdepth - 1) {
            return null;
        }

        if (\count($var) < self::$trigger) {
            return null;
        }

        if (self::$numeric_only && Utils::isAssoc($var)) {
            return null;
        }

        $slice = \array_slice($var, 0, self::$limit, true);
        $array = $parser->parse($slice, $c);

        if (!\is_array($array->value->contents ?? null)) {
            return null;
        }

        $base = new BaseContext($c->getName());
        $base->depth = $pdepth - 1;
        $base->access_path = $c->getAccessPath();

        $slice = \array_slice($var, self::$limit, null, true);
        $slice = $parser->parse($slice, $base);

        if (!\is_array($slice->value->contents ?? null)) {
            return null;
        }

        /**
         * @psalm-var Representation $array->value
         * @psalm-var Value[] $array->value->contents
         * @psalm-var Value[] $slice->value->contents
         * Psalm bug #11055
         */
        foreach ($slice->value->contents as $child) {
            $this->replaceDepthLimit($child, $cdepth + 1);
        }

        $array->value->contents = \array_merge($array->value->contents, $slice->value->contents);
        $array->size = \count($var);

        // Annoyingly some parsers will add their own (Broken) representations that we'll need to strip off
        // Most notably TablePlugin, but also potentially (Though unlikely) TracePlugin
        $profile = $array->getRepresentation('profiling');
        $array->clearRepresentations();
        $array->addRepresentation($array->value);
        // Make an exception for profile plugin
        if ($profile instanceof ProfileRepresentation) {
            $array->addRepresentation($profile, 0);
        }

        return $array;
    }

    protected function replaceDepthLimit(Value $v, int $depth): void
    {
        $c = $v->getContext();

        if ($c instanceof BaseContext) {
            $c->depth = $depth;
        }

        $pdepth = $this->getParser()->getDepthLimit();

        if (isset($v->hints['depth_limit']) && $pdepth && $depth < $pdepth) {
            unset($v->hints['depth_limit']);
            $v->hints['array_limit'] = true;
        }

        $reps = $v->getRepresentations();
        if ($v->value && !\in_array($v->value, $reps, true)) {
            $reps[] = $v->value;
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
