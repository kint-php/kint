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

use Kint\Zval\Representation\SplFileInfoRepresentation;
use Kint\Zval\Value;
use SplFileInfo;
use TypeError;

class FsPathPlugin extends AbstractPlugin
{
    public static array $blacklist = ['/', '.'];

    public function getTypes(): array
    {
        return ['string'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (\strlen($var) > 2048) {
            return;
        }

        if (!\preg_match('/[\\/\\'.DIRECTORY_SEPARATOR.']/', $var)) {
            return;
        }

        if (\preg_match('/[?<>"*|]/', $var)) {
            return;
        }

        try {
            if (!@\file_exists($var)) {
                return;
            }
        } catch (TypeError $e) {// @codeCoverageIgnore
            // Only possible in PHP 7
            return; // @codeCoverageIgnore
        }

        if (\in_array($var, self::$blacklist, true)) {
            return;
        }

        $r = new SplFileInfoRepresentation(new SplFileInfo($var));
        $r->hints['fspath'] = true;
        $o->addRepresentation($r, 0);
    }
}
