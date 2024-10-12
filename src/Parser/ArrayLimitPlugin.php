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
use Kint\Zval\AbstractValue;
use Kint\Zval\ArrayValue;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\Context\ContextInterface;
use Kint\Zval\Representation\ProfileRepresentation;
use Kint\Zval\Representation\Representation;

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

    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
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

        if (!$array instanceof ArrayValue) {
            return null;
        }

        $base = new BaseContext($c->getName());
        $base->depth = $pdepth - 1;
        $base->access_path = $c->getAccessPath();

        $slice = \array_slice($var, self::$limit, null, true);
        $slice = $parser->parse($slice, $base);

        if (!$slice instanceof ArrayValue) {
            return null;
        }

        foreach ($slice->getContents() as $child) {
            $this->replaceDepthLimit($child, $cdepth + 1);
        }

        $out = new ArrayValue($c, \count($var), \array_merge($array->getContents(), $slice->getContents()));
        $out->appendHints($array->getHints());

        // Explicitly copy over profile plugin
        $profile = $array->getRepresentation('profiling');
        if ($profile instanceof ProfileRepresentation) {
            $slicep = $slice->getRepresentation('profiling');
            if ($slicep instanceof ProfileRepresentation) {
                $profile->complexity += $slicep->complexity;
            }

            $out->addRepresentation($profile);
        }

        // Add contents
        $rep = new Representation('Contents');
        $rep->implicit_label = true;
        $rep->contents = $out->getContents();
        $out->addRepresentation($rep);

        return $out;
    }

    protected function replaceDepthLimit(AbstractValue $v, int $depth): void
    {
        $c = $v->getContext();

        if ($c instanceof BaseContext) {
            $c->depth = $depth;
        }

        $pdepth = $this->getParser()->getDepthLimit();

        if ($v->hasHint('depth_limit') && $pdepth && $depth < $pdepth) {
            $v->removeHint('depth_limit');
            $v->addHint('array_limit');
        }

        $reps = $v->getRepresentations();

        foreach ($reps as $rep) {
            if ($rep->contents instanceof AbstractValue) {
                $this->replaceDepthLimit($rep->contents, $depth + 1);
            } elseif (\is_array($rep->contents)) {
                foreach ($rep->contents as $child) {
                    $this->replaceDepthLimit($child, $depth + 1);
                }
            }
        }
    }
}
