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
use Kint\Zval\ResourceValue;
use Kint\Zval\StreamValue;
use Kint\Zval\Value;
use TypeError;

class StreamPlugin extends AbstractPlugin
{
    public function getTypes(): array
    {
        return ['resource'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (!$o instanceof ResourceValue) {
            return;
        }

        // Doublecheck that the resource is open before we get the metadata
        if (!\is_resource($var)) {
            return;
        }

        try {
            $meta = \stream_get_meta_data($var);
        } catch (TypeError $e) {
            return;
        }

        $rep = new Representation('Stream');
        $rep->implicit_label = true;
        $rep->contents = [];

        $parser = $this->getParser();
        foreach ($meta as $key => $val) {
            $base_obj = new Value($key);
            $base_obj->depth = $o->depth + 1;
            $base_obj->access = Value::ACCESS_NONE;
            $base_obj->operator = Value::OPERATOR_ARRAY;

            if (null !== $o->access_path) {
                $base_obj->access_path = 'stream_get_meta_data('.$o->access_path.')['.\var_export($key, true).']';
            }

            $rep->contents[] = $parser->parse($val, $base_obj);
        }

        $o->addRepresentation($rep, 0);
        $o->value = $rep;

        $stream = new StreamValue($o->name, $meta);
        $stream->transplant($o);
        $o = $stream;
    }
}
