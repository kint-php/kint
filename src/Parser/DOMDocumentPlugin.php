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

use DOMAttr;
use DOMComment;
use DOMDocument;
use DOMNamedNodeMap;
use DOMNode;
use DOMNodeList;
use DOMText;
use InvalidArgumentException;
use Kint\Zval\BlobValue;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

/**
 * The DOMDocument parser plugin is particularly useful as it is both the only
 * way to see inside the DOMNode without print_r, and the only way to see mixed
 * text and node inside XML (SimpleXMLElement will strip out the text).
 */
class DOMDocumentPlugin extends AbstractPlugin
{
    /**
     * List of properties to skip parsing.
     *
     * The properties of a DOMNode can do a *lot* of damage to debuggers. The
     * DOMNode contains not one, not two, not three, not four, not 5, not 6,
     * not 7 but 8 different ways to recurse into itself:
     * * firstChild
     * * lastChild
     * * previousSibling
     * * nextSibling
     * * ownerDocument
     * * parentNode
     * * childNodes
     * * attributes
     *
     * All of this combined: the tiny SVGs used as the caret in Kint are already
     * enough to make parsing and rendering take over a second, and send memory
     * usage over 128 megs. So we blacklist every field we don't strictly need
     * and hope that that's good enough.
     *
     * In retrospect - this is probably why print_r does the same
     *
     * @psalm-var array<string, class-string>
     */
    public static array $blacklist = [
        'parentNode' => DOMNode::class,
        'firstChild' => DOMNode::class,
        'lastChild' => DOMNode::class,
        'previousSibling' => DOMNode::class,
        'nextSibling' => DOMNode::class,
        'ownerDocument' => DOMDocument::class,
    ];

    /**
     * Show all properties and methods.
     */
    public static bool $verbose = false;

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
        if (!$o instanceof InstanceValue) {
            return;
        }

        if ($var instanceof DOMNamedNodeMap || $var instanceof DOMNodeList) {
            $this->parseList($var, $o, $trigger);

            return;
        }

        if ($var instanceof DOMNode) {
            $this->parseNode($var, $o);

            return;
        }
    }

    /**
     * @param DOMNamedNodeMap|DOMNodeList &$var
     */
    protected function parseList($var, InstanceValue &$o, int $trigger): void
    {
        if (!$var instanceof DOMNamedNodeMap && !$var instanceof DOMNodeList) {
            return;
        }

        // Recursion should never happen, should always be stopped at the parent
        // DOMNode. Depth limit on the other hand we're going to skip since
        // that would show an empty iterator and rather useless. Let the depth
        // limit hit the children (DOMNodeList only has DOMNode as children)
        if ($trigger & Parser::TRIGGER_RECURSION) {
            return;
        }

        $o->size = $var->length;
        if (0 === $o->size) {
            $o->replaceRepresentation(new Representation('Iterator'));
            $o->size = null;

            return;
        }

        $parser = $this->getParser();

        // Depth limit
        // Make empty iterator representation since we need it in DOMNode to point out depth limits
        if ($parser->getDepthLimit() && $o->depth + 1 >= $parser->getDepthLimit()) {
            $b = new Value($o->classname.' Iterator Contents');
            $b->depth = $o->depth + 1;
            $b->hints[] = 'depth_limit';

            if (null !== $o->access_path) {
                $b->access_path = 'iterator_to_array('.$o->access_path.')';
            }

            $r = new Representation('Iterator');
            $r->contents = [$b];
            $o->replaceRepresentation($r, 0);

            return;
        }

        $r = new Representation('Iterator');
        $r->contents = [];
        $o->replaceRepresentation($r, 0);

        foreach ($var as $key => $item) {
            $base_obj = new Value($item->nodeName);
            $base_obj->depth = $o->depth + 1;

            if (null !== $o->access_path) {
                if ($var instanceof DOMNamedNodeMap) {
                    // We can't use getNamedItem() for attributes without a
                    // namespace because it will pick the first matching
                    // attribute of *any* namespace.
                    //
                    // Contrary to the PHP docs, getNamedItemNS takes null
                    // as a namespace argument for an unnamespaced item.
                    $base_obj->access_path = $o->access_path.'->getNamedItemNS(';
                    $base_obj->access_path .= \var_export($item->namespaceURI, true);
                    $base_obj->access_path .= ', ';
                    $base_obj->access_path .= \var_export($item->name, true);
                    $base_obj->access_path .= ')';
                } else { // DOMNodeList
                    $base_obj->access_path = $o->access_path.'->item('.\var_export($key, true).')';
                }
            }

            $r->contents[] = $parser->parse($item, $base_obj);
        }
    }

    /**
     * @psalm-param-out Value &$o
     */
    protected function parseNode(DOMNode $var, InstanceValue &$o): void
    {
        // Fill the properties
        // They can't be enumerated through reflection or casting,
        // so we have to trust the docs and try them one at a time
        $known_properties = [
            'nodeValue',
            'childNodes',
            'attributes',
        ];

        if (self::$verbose) {
            $known_properties = [
                'nodeName',
                'nodeValue',
                'nodeType',
                'parentNode',
                'childNodes',
                'firstChild',
                'lastChild',
                'previousSibling',
                'nextSibling',
                'attributes',
                'ownerDocument',
                'namespaceURI',
                'prefix',
                'localName',
                'baseURI',
                'textContent',
            ];
        }

        $childNodesInstance = null;
        $childNodes = null;
        $attributes = null;

        $rep = $o->value;

        if (null === $rep) {
            return;
        }

        if (!\is_array($rep->contents ?? null)) {
            $rep->contents = [];
        }

        foreach ($known_properties as $prop) {
            $prop_obj = $this->parseProperty($o, $prop, $var);
            /** @psalm-var Value[] $rep->contents */
            $rep->contents[] = $prop_obj;

            if ('childNodes' === $prop) {
                if ((!$prop_obj instanceof InstanceValue) || DOMNodeList::class !== $prop_obj->classname) {
                    throw new InvalidArgumentException('DOMNode->childNodes must be instance of DOMNodeList');
                }

                $childNodesInstance = $prop_obj;
                $childNodes = $prop_obj->getRepresentation('iterator');
            } elseif ('attributes' === $prop) {
                $attributes = $prop_obj->getRepresentation('iterator');
            }
        }

        if (!self::$verbose) {
            $o->removeRepresentation('methods');
            $o->removeRepresentation('properties');
        }

        // Attributes and comments and text nodes don't
        // need children or attributes of their own
        if (\in_array($o->classname, [DOMAttr::class, DOMText::class, DOMComment::class], true)) {
            $o = self::textualNodeToString($o);

            return;
        }

        // Set the attributes
        if ($attributes) {
            $a = new Representation('Attributes');
            $a->contents = $attributes->contents;
            $o->addRepresentation($a, 0);
        }

        // Set the children
        if ($childNodes && \is_array($childNodes->contents) && $childNodesInstance) {
            $childNodes = $childNodes->contents;

            $c = new Representation('Children');
            $c->contents = [];

            if (1 === \count($childNodes) && ($node = \reset($childNodes)) && \in_array('depth_limit', $node->hints, true)) {
                $n = new InstanceValue(
                    $node->name,
                    $childNodesInstance->classname,
                    $childNodesInstance->spl_object_hash,
                    $childNodesInstance->spl_object_id
                );
                $n->transplant($node);
                $n->name = 'childNodes';
                $n->classname = DOMNodeList::class;
                $c->contents = [$n];
            } else {
                foreach ($childNodes as $node) {
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

            $o->addRepresentation($c, 0);

            $o->size = \count($childNodes);
        }

        if (0 === $o->size) {
            $o->size = null;
        }
    }

    protected function parseProperty(InstanceValue $o, string $prop, DOMNode &$var): Value
    {
        // Duplicating (And slightly optimizing) the Parser::parseObject() code here
        $base_obj = new Value($prop);
        $base_obj->depth = $o->depth + 1;
        $base_obj->owner_class = $o->classname;
        $base_obj->operator = Value::OPERATOR_OBJECT;
        $base_obj->access = Value::ACCESS_PUBLIC;

        if (null !== $o->access_path) {
            $base_obj->access_path = $o->access_path;

            /**
             * @psalm-var string $base_obj->name
             * We set this to the $prop property in the initialization so we know the type already
             */
            if (\preg_match('/^[A-Za-z0-9_]+$/', $base_obj->name)) {
                $base_obj->access_path .= '->'.$base_obj->name;
            } else {
                $base_obj->access_path .= '->{'.\var_export($base_obj->name, true).'}';
            }
        }

        if (!isset($var->{$prop})) {
            $base_obj->type = 'null';
        } elseif (isset(self::$blacklist[$prop])) {
            $b = new InstanceValue($base_obj->name, self::$blacklist[$prop], \spl_object_hash($var->{$prop}), \spl_object_id($var->{$prop}));
            $b->transplant($base_obj);
            $base_obj = $b;

            $base_obj->hints[] = 'blacklist';
        } else {
            $parser = $this->getParser();
            if ('attributes' === $prop && $parser->getDepthLimit() && $parser->getDepthLimit() - 2 < $base_obj->depth) {
                $base_obj->depth = $parser->getDepthLimit() - 2;
            }
            $base_obj = $parser->parse($var->{$prop}, $base_obj);
        }

        return $base_obj;
    }

    protected static function textualNodeToString(InstanceValue $o): Value
    {
        if (!\is_array($o->value->contents ?? null)) {
            throw new InvalidArgumentException('Invalid DOMNode passed to DOMDocumentPlugin::textualNodeToString');
        }

        if (!\in_array($o->classname, [DOMText::class, DOMAttr::class, DOMComment::class], true)) {
            throw new InvalidArgumentException('Invalid DOMNode passed to DOMDocumentPlugin::textualNodeToString');
        }

        /**
         * @psalm-var Value[] $o->value->contents
         * Psalm bug #11052
         */
        foreach ($o->value->contents as $property) {
            if ('nodeValue' === $property->name) {
                $ret = clone $property;
                $ret->name = $o->name;

                return $ret;
            }
        }

        throw new InvalidArgumentException('Invalid DOMNode passed to DOMDocumentPlugin::textualNodeToString');
    }
}
