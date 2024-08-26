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

use DOMAttr as Attr;
use DOMCharacterData as CharacterData;
use DOMDocumentType as DocumentType;
use DOMElement as Element;
use DOMNamedNodeMap as NamedNodeMap;
use DOMNode as Node;
use DOMNodeList as NodeList;
use DOMText as Text;
use InvalidArgumentException;
use Kint\Zval\BlobValue;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

/**
 * Backported from DomPlugin, if comments are missing check there.
 *
 * @see DomPlugin
 */
class DOMDocumentPlugin extends AbstractPlugin
{
    public const NODE_PROPS = [
        'nodeName' => true,
        'nodeValue' => false,
        'nodeType' => true,
        'parentNode' => true,
        'parentElement' => true,
        'childNodes' => true,
        'firstChild' => true,
        'lastChild' => true,
        'previousSibling' => true,
        'nextSibling' => true,
        'attributes' => true,
        'isConnected' => true,
        'ownerDocument' => true,
        'namespaceURI' => true,
        'prefix' => false,
        'localName' => true,
        'baseURI' => true,
        'textContent' => false,
    ];

    public const ELEMENT_PROPS = [
        'tagName' => true,
        'className' => false,
        'id' => false,
        'schemaTypeInfo' => true,
        'firstElementChild' => true,
        'lastElementChild' => true,
        'childElementCount' => true,
        'previousElementSibling' => true,
        'nextElementSibling' => true,
    ];

    public const ELEMENT_VERSIONS = [
        'className' => KINT_PHP83,
        'id' => KINT_PHP83,
        'firstElementChild' => KINT_PHP80,
        'lastElementChild' => KINT_PHP80,
        'childElementCount' => KINT_PHP80,
        'previousElementSibling' => KINT_PHP80,
        'nextElementSibling' => KINT_PHP80,
    ];

    /**
     * @psalm-var array<string, true>
     */
    public static array $blacklist = [
        'parentNode' => true,
        'parentElement' => true,
        'firstChild' => true,
        'lastChild' => true,
        'previousSibling' => true,
        'nextSibling' => true,
        'ownerDocument' => true,
        'firstElementChild' => true,
        'lastElementChild' => true,
        'previousElementSibling' => true,
        'nextElementSibling' => true,
    ];

    public static bool $verbose = false;

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS | Parser::TRIGGER_DEPTH_LIMIT;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (!$o instanceof InstanceValue) {
            return;
        }

        if (\is_a($o->classname, Attr::class, true) || \is_a($o->classname, CharacterData::class, true)) {
            $o = $this->parseText($var, $o);

            return;
        }

        if ($var instanceof NamedNodeMap || $var instanceof NodeList) {
            $this->parseList($var, $o, $trigger);

            return;
        }

        if ($trigger & Parser::TRIGGER_DEPTH_LIMIT) {
            return;
        }

        if ($var instanceof Node) {
            $this->parseNode($var, $o);

            return;
        }
    }

    protected function parseProperty(Node $var, string $prop, InstanceValue $parent): Value
    {
        $base_obj = new Value($prop);
        $base_obj->depth = $parent->depth + 1;
        $base_obj->owner_class = $parent->classname;
        $base_obj->operator = Value::OPERATOR_OBJECT;
        $base_obj->access = Value::ACCESS_PUBLIC;

        if (null !== $parent->access_path) {
            $base_obj->access_path = $parent->access_path.'->'.$base_obj->name;
        }

        if (!isset($var->{$prop})) {
            $base_obj->type = 'null';

            return $base_obj;
        }

        if (isset(self::$blacklist[$prop])) {
            $b = new InstanceValue($base_obj->name, \get_class($var->{$prop}), \spl_object_hash($var->{$prop}), \spl_object_id($var->{$prop}));
            $b->transplant($base_obj);
            $b->hints[] = 'blacklist';

            return $b;
        }

        return $this->getParser()->parse($var->{$prop}, $base_obj);
    }

    /**
     * @param Attr|CharacterData $var
     */
    private function parseText($var, InstanceValue $o): Value
    {
        --$o->depth;
        $ret = $this->parseProperty($var, 'nodeValue', $o);
        $ret->name = $o->name;
        $ret->operator = $o->operator;
        $ret->access = $o->access;
        $ret->readonly = $o->readonly;

        return $ret;
    }

    /**
     * @param NamedNodeMap|NodeList $var
     */
    private function parseList($var, InstanceValue &$o, int $trigger): void
    {
        $o->size = $var->length;
        if (0 === $o->size) {
            $o->replaceRepresentation(new Representation('Iterator'));
            $o->size = null;

            return;
        }

        $r = new Representation('Iterator');
        $r->contents = [];
        $o->replaceRepresentation($r, 0);

        $parser = $this->getParser();

        // Depth limit
        // Make empty iterator representation since we need it to point out depth limits
        if ($trigger & Parser::TRIGGER_DEPTH_LIMIT) {
            $b = new Value($o->classname.' Iterator Contents');
            $b->depth = $o->depth + 1;
            $b->hints[] = 'depth_limit';

            if (null !== $o->access_path) {
                $b->access_path = 'iterator_to_array('.$o->access_path.')';
            }

            $r->contents = [$b];

            return;
        }

        $o->hints[] = 'iterator_primary';

        foreach ($var as $key => $item) {
            $base_obj = new Value($item->nodeName);
            $base_obj->depth = $o->depth + 1;

            if (null !== $o->access_path) {
                if ($var instanceof NamedNodeMap) {
                    // We can't use getNamedItem() for attributes without a
                    // namespace because it will pick the first matching
                    // attribute of *any* namespace.
                    //
                    // Contrary to the PHP docs, getNamedItemNS takes null
                    // as a namespace argument for an unnamespaced item.
                    $base_obj->access_path = $o->access_path.'->getNamedItem('.\var_export($item->nodeName, true).')';
                } else { // Dom\NodeList
                    $base_obj->access_path = $o->access_path.'->item('.\var_export($key, true).')';
                }
            }

            $r->contents[] = $parser->parse($item, $base_obj);
        }
    }

    private function parseNode(Node $var, InstanceValue &$o): void
    {
        if (self::$verbose) {
            if ($var instanceof Element) {
                $known_properties = self::ELEMENT_PROPS + self::NODE_PROPS;
            } else {
                $known_properties = self::NODE_PROPS;
            }

            foreach (self::ELEMENT_VERSIONS as $key => $val) {
                /**
                 * @psalm-suppress TypeDoesNotContainType
                 * Psalm bug #4509
                 */
                if (false === $val) {
                    unset($known_properties[$key]);
                }
            }
        } else {
            $o->removeRepresentation('methods');
            $o->removeRepresentation('properties');
            $o->removeRepresentation('statics');

            $known_properties = [
                'nodeValue' => false,
                'childNodes' => true,
                'attributes' => true,
            ];
        }

        if (null === $o->value) {
            return;
        }

        $o->value->contents = [];

        if ($var instanceof DocumentType && $o->name === $var->nodeName) {
            $o->name = '!DOCTYPE '.$o->name;
        }

        $o->size = null;
        $c = null;
        $a = null;

        foreach ($known_properties as $prop => $readonly) {
            $o->value->contents[] = $prop_obj = $this->parseProperty($var, $prop, $o);
            $prop_obj->readonly = $readonly;

            if ('childNodes' === $prop) {
                if ((!$prop_obj instanceof InstanceValue) || NodeList::class !== $prop_obj->classname) {
                    throw new InvalidArgumentException('DOMNode->childNodes must be instance of DOMNodeList'); // @codeCoverageIgnore
                }

                $c = self::getChildrenRepresentation($prop_obj);
            } elseif ('attributes' === $prop) {
                if (\in_array('depth_limit', $prop_obj->hints, true)) {
                    $prop_obj->hints = \array_diff($prop_obj->hints, ['depth_limit']);
                    $this->parse($var->attributes, $prop_obj, Parser::TRIGGER_SUCCESS);
                }
                $a = $prop_obj->getRepresentation('iterator');
            }
        }

        // We do this separately here so children representation shows
        // up before attributes regardless of supposed parameter order
        if ($a) {
            $attributes = $a->contents;
            $a = new Representation('Attributes');
            $a->contents = $attributes;
            $o->addRepresentation($a, 0);
        }
        if ($c) {
            $o->addRepresentation($c, 0);
            /** @psalm-var Value[] $c->contents */
            $o->size = \count($c->contents);
        }
    }

    private static function getChildrenRepresentation(InstanceValue $property): ?Representation
    {
        $iter = $property->getRepresentation('iterator');

        if (null === $iter || !\is_array($iter->contents)) {
            return null; // @codeCoverageIgnore
        }

        $contents = $iter->contents;

        $c = new Representation('Children');
        $c->implicit_label = true;
        $c->contents = [];

        // In this case we've found the depth_limit Iterator Contents from parseList()
        if (1 === \count($contents) && ($node = \reset($contents)) && Value::class === \get_class($node) && \in_array('depth_limit', $node->hints, true)) {
            $n = new InstanceValue(
                'childNodes',
                $property->classname,
                $property->spl_object_hash,
                $property->spl_object_id
            );
            $n->transplant($node);
            $n->name = 'childNodes';
            $c->contents = [$n];
        } else {
            foreach ($contents as $node) {
                // Remove text nodes if theyre empty
                if ($node instanceof BlobValue && '#text' === $node->name) {
                    /**
                     * @psalm-suppress InvalidArgument
                     * Psalm bug #11055
                     */
                    if (!\is_string($node->value->contents ?? null) || \ctype_space($node->value->contents) || '' === $node->value->contents) {
                        continue;
                    }
                }

                $c->contents[] = $node;
            }
        }

        if (0 === \count($c->contents)) {
            return null;
        }

        return $c;
    }
}
