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

use Dom\NamedNodeMap;
use Dom\NodeList;
use DOMNamedNodeMap;
use DOMNodeList;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
use mysqli_result;
use PDOStatement;
use SimpleXMLElement;
use SplFileObject;
use Traversable;

class IteratorPlugin extends AbstractPlugin
{
    /**
     * List of classes and interfaces to blacklist.
     *
     * Certain classes (Such as PDOStatement) irreversibly lose information
     * when traversed. Others are just huge. Either way, put them in here
     * and you won't have to worry about them being parsed.
     *
     * @psalm-var class-string<Traversable>[]
     */
    public static array $blacklist = [
        NamedNodeMap::class,
        NodeList::class,
        DOMNamedNodeMap::class,
        DOMNodeList::class,
        mysqli_result::class,
        PDOStatement::class,
        SimpleXMLElement::class,
        SplFileObject::class,
    ];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (!$var instanceof Traversable || $o->getRepresentation('iterator')) {
            return;
        }

        foreach (self::$blacklist as $class) {
            /**
             * @psalm-suppress RedundantCondition
             * Psalm bug #11076
             */
            if ($var instanceof $class) {
                $b = new Value($class.' Iterator Contents');
                $b->depth = $o->depth + 1;
                $b->hints['blacklist'] = true;

                if (null !== $o->access_path) {
                    $b->access_path = 'iterator_to_array('.$o->access_path.', true)';
                }

                $r = new Representation('Iterator');
                $r->contents = [$b];

                $o->addRepresentation($r);

                return;
            }
        }

        $data = \iterator_to_array($var);

        $base_obj = new Value('base');
        $base_obj->depth = $o->depth;

        if (null !== $o->access_path) {
            $base_obj->access_path = 'iterator_to_array('.$o->access_path.')';
        }

        $r = new Representation('Iterator');
        /**
         * @psalm-var object{contents: array} $r->contents->value
         * Since we didn't get TRIGGER_DEPTH_LIMIT and set the iterator to the
         * same depth we can assume at least 1 level deep will exist
         */
        $r->contents = $this->getParser()->parse($data, $base_obj);
        $r->contents = $r->contents->value->contents;

        $primary = $o->getRepresentations();
        $primary = \reset($primary);
        if ($primary && $primary === $o->value && [] === $primary->contents) {
            $o->addRepresentation($r, 0);
            $o->hints['iterator_primary'] = true;
            $o->size = \count($data) ?: null;
        } else {
            $o->addRepresentation($r);
        }
    }
}
