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

use Dom\HTMLDocument;
use DOMException;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

class HtmlPlugin extends AbstractPlugin
{
    protected DomPlugin $dom_plugin;

    public function __construct(Parser $parser)
    {
        parent::__construct($parser);

        $this->dom_plugin = new DomPlugin($parser);
    }

    public function setParser(Parser $p): void
    {
        parent::setParser($p);

        $this->dom_plugin->setParser($p);
    }

    public function getTypes(): array
    {
        return ['string'];
    }

    public function getTriggers(): int
    {
        if (!KINT_PHP84) {
            return Parser::TRIGGER_NONE;
        }

        return Parser::TRIGGER_SUCCESS;
    }

    /**
     * @codeCoverageIgnore
     */
    public function parse(&$var, Value &$o, int $trigger): void
    {
        if ('<!DOCTYPE html>' !== \substr($var, 0, 15)) {
            return;
        }

        try {
            /**
             * @psalm-suppress UndefinedClass
             * Psalm bug #11079
             */
            $html = HTMLDocument::createFromString($var, LIBXML_NOERROR);
        } catch (DOMException $e) {
            return;
        }

        $base_obj = new InstanceValue('childNodes', \get_class($html->childNodes), \spl_object_hash($html->childNodes), \spl_object_id($html->childNodes));

        if (null !== $o->access_path) {
            $base_obj->access_path = '\\Dom\\HTMLDocument::createFromString('.$o->access_path.')->childNodes';
        }

        $this->dom_plugin->parse($html->childNodes, $base_obj, Parser::TRIGGER_SUCCESS);

        $iter = $base_obj->getRepresentation('iterator');
        if (!\is_array($iter->contents ?? null)) {
            return;
        }

        $r = new Representation('HTML');
        $r->contents = $iter->contents;

        $o->addRepresentation($r, 0);
    }
}
