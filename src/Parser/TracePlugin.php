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

use Kint\Utils;
use Kint\Zval\Representation\Representation;
use Kint\Zval\TraceFrameValue;
use Kint\Zval\TraceValue;
use Kint\Zval\Value;

/**
 * @psalm-import-type TraceFrame from TraceFrameValue
 */
class TracePlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public static array $blacklist = ['spl_autoload_call'];
    public static array $path_blacklist = [];

    public function getTypes(): array
    {
        return ['array'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, Value $v, int $trigger): Value
    {
        if (!$v->value) {
            return $v;
        }

        // Shallow copy so we don't have to worry about touching var
        $trace = $var;

        /**
         * @psalm-suppress PossiblyInvalidArgument
         * Psalm bug #11055
         */
        if (!\is_array($v->value->contents ?? null) || \count($trace) !== \count($v->value->contents) || !Utils::isTrace($trace)) {
            return $v;
        }

        $pdepth = $this->getParser()->getDepthLimit();
        $c = $v->getContext();

        // We need at least 2 levels in order to get $trace[n]['args']
        if ($pdepth && $c->getDepth() + 2 >= $pdepth) {
            return $v;
        }

        $traceobj = new TraceValue($c);
        $traceobj->transplant($v);
        /** @psalm-var Representation $rep */
        $rep = $traceobj->value;

        /** @psalm-var Value[] $rep->contents */
        $old_trace = $rep->contents;

        self::$blacklist = Utils::normalizeAliases(self::$blacklist);
        $path_blacklist = self::normalizePaths(self::$path_blacklist);

        $rep->contents = [];

        foreach ($old_trace as $frame) {
            $index = $frame->getContext()->getName();

            if (!isset($trace[$index]) || Utils::traceFrameIsListed($trace[$index], self::$blacklist)) {
                continue;
            }

            /**
             * @psalm-var TraceFrame $trace[$index]
             * Psalm bug #11115
             */
            if (isset($trace[$index]['file']) && false !== ($realfile = \realpath($trace[$index]['file']))) {
                foreach ($path_blacklist as $path) {
                    if (0 === \strpos($realfile, $path)) {
                        continue 2;
                    }
                }
            }

            $rep->contents[$index] = new TraceFrameValue($frame, $trace[$index]);
        }

        \ksort($rep->contents);
        $rep->contents = \array_values($rep->contents);

        $traceobj->clearRepresentations();
        $traceobj->addRepresentation($rep);
        $traceobj->size = \count($rep->contents);

        return $traceobj;
    }

    protected static function normalizePaths(array $paths): array
    {
        $normalized = [];

        foreach ($paths as $path) {
            $realpath = \realpath($path);
            if (\is_dir($realpath)) {
                $realpath .= DIRECTORY_SEPARATOR;
            }

            $normalized[] = $realpath;
        }

        return $normalized;
    }
}
