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

use JsonException;
use Kint\Zval\AbstractValue;
use Kint\Zval\ArrayValue;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\Representation\Representation;

class JsonPlugin extends AbstractPlugin implements PluginCompleteInterface
{
    public function getTypes(): array
    {
        return ['string'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
    {
        if (!isset($var[0]) || ('{' !== $var[0] && '[' !== $var[0])) {
            return $v;
        }

        try {
            $json = \json_decode($var, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $v;
        }

        $json = (array) $json;

        $c = $v->getContext();

        $base = new BaseContext('JSON Decode');
        $base->depth = $c->getDepth();

        if (null !== ($ap = $c->getAccessPath())) {
            $base->access_path = 'json_decode('.$ap.', true)';
        }

        $r = new Representation('Json');
        $r->contents = $this->getParser()->parse($json, $base);

        if ($r->contents instanceof ArrayValue && !$r->contents->hasHint('depth_limit')) {
            $r->contents = $r->contents->getContents();
        }

        $v->addRepresentation($r, 0);

        return $v;
    }
}
