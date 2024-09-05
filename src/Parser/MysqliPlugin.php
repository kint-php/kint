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
use Kint\Zval\Value;
use mysqli;
use Throwable;

/**
 * Adds support for mysqli object parsing.
 *
 * Due to the way mysqli is implemented in PHP, this will cause
 * warnings on certain mysqli objects if screaming is enabled.
 */
class MysqliPlugin extends AbstractPlugin
{
    // These 'properties' are actually globals
    public const ALWAYS_READABLE = [
        'client_version' => true,
        'connect_errno' => true,
        'connect_error' => true,
    ];

    // These are readable on empty mysqli objects, but not on failed connections
    public const EMPTY_READABLE = [
        'client_info' => true,
        'errno' => true,
        'error' => true,
    ];

    // These are only readable on connected mysqli objects
    public const CONNECTED_READABLE = [
        'affected_rows' => true,
        'error_list' => true,
        'field_count' => true,
        'host_info' => true,
        'info' => true,
        'insert_id' => true,
        'server_info' => true,
        'server_version' => true,
        'sqlstate' => true,
        'protocol_version' => true,
        'thread_id' => true,
        'warning_count' => true,
    ];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_COMPLETE;
    }

    /**
     * Before 8.1: Properties were nulls when cast to array
     * After 8.1: Properties are readonly and uninitialized when cast to array (Aka missing).
     */
    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (!$var instanceof mysqli || !$o instanceof InstanceValue) {
            return;
        }

        /**
         * @psalm-var object{contents: Value[]} $o->value
         * @psalm-var ?string $var->sqlstate
         * */
        try {
            $connected = \is_string(@$var->sqlstate);
        } catch (Throwable $t) {
            $connected = false;
        }

        /** @psalm-var ?string $var->client_info */
        try {
            $empty = !$connected && \is_string(@$var->client_info);
        } catch (Throwable $t) { // @codeCoverageIgnore
            // Only possible in PHP 8.0. Before 8.0 there's no exception,
            // after 8.1 there are no failed connection objects
            $empty = false; // @codeCoverageIgnore
        }

        $parser = $this->getParser();

        foreach ($o->value->contents as $key => $obj) {
            if (isset(self::CONNECTED_READABLE[$obj->name])) {
                $obj->readonly = KINT_PHP81;
                if (!$connected) {
                    // No failed connections after PHP 8.1
                    continue; // @codeCoverageIgnore
                }
            } elseif (isset(self::EMPTY_READABLE[$obj->name])) {
                $obj->readonly = KINT_PHP81;
                // No failed connections after PHP 8.1
                if (!$connected && !$empty) { // @codeCoverageIgnore
                    continue; // @codeCoverageIgnore
                }
            } elseif (!isset(self::ALWAYS_READABLE[$obj->name])) {
                continue;
            }

            $obj->readonly = KINT_PHP81;

            // Only handle unparsed properties
            if ((KINT_PHP81 ? 'uninitialized' : 'null') !== $obj->type) {
                continue;
            }

            $param = $var->{$obj->name};

            // If it really was a null
            if (!KINT_PHP81 && null === $param) {
                continue; // @codeCoverageIgnore
            }

            $base = new Value($obj->name);
            $base->transplant($obj);

            $o->value->contents[$key] = $parser->parse($param, $base);
        }
    }
}
