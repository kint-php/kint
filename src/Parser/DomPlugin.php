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

use Dom\Attr;
use Dom\CharacterData;
use Dom\Document;
use Dom\DocumentType;
use Dom\Element;
use Dom\HTMLElement;
use Dom\NamedNodeMap;
use Dom\Node;
use Dom\NodeList;
use Kint\Zval\BlobValue;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
use LogicException;

/**
 * This is for parsing the new PHP 8.4 Dom tools which are spec-compliant and
 * support HTML. Most of this code originated in DOMDocumentPlugin and hopefully
 * we can one day bump the min version to 8.4 and replace it with importLegacyNode.
 */
class DomPlugin extends AbstractPlugin
{
    /**
     * Dom\Node casts to an empty array so we have to loop
     * through the properties and parse them one at a time.
     *
     * Additionally, textContent becomes readonly in documents and nodeValue
     * becomes readonly in documents and elements
     *
     * @psalm-var array<string, bool> Property names to readable status
     */
    public const NODE_PROPS = [
        'nodeType' => true,
        'nodeName' => true,
        'baseURI' => true,
        'isConnected' => true,
        'ownerDocument' => true,
        'parentNode' => true,
        'parentElement' => true,
        'childNodes' => true,
        'firstChild' => true,
        'lastChild' => true,
        'previousSibling' => true,
        'nextSibling' => true,
        // Technically nodeValue not always readonly but only writable on
        // Attr and CharacterData where we're not parsing props anyway
        'nodeValue' => true,
        'textContent' => false,
    ];

    /**
     * @psalm-var array<string, bool> Property names to readable status
     */
    public const ELEMENT_PROPS = [
        'namespaceURI' => true,
        'prefix' => true,
        'localName' => true,
        'tagName' => true,
        'id' => false,
        'className' => false,
        'classList' => true,
        'attributes' => true,
        'firstElementChild' => true,
        'lastElementChild' => true,
        'childElementCount' => true,
        'previousElementSibling' => true,
        'nextElementSibling' => true,
        'innerHTML' => false,
        'substitutedNodeValue' => false,
    ];

    /**
     * List of properties to skip parsing.
     *
     * The properties of a Dom\Node can do a *lot* of damage to debuggers. The
     * Dom\Node contains not one, not two, but 13 different ways to recurse into itself:
     * * parentNode
     * * firstChild
     * * lastChild
     * * previousSibling
     * * nextSibling
     * * parentElement
     * * firstElementChild
     * * lastElementChild
     * * previousElementSibling
     * * nextElementSibling
     * * childNodes
     * * attributes
     * * ownerDocument
     *
     * All of this combined: the tiny SVGs used as the caret in Kint were already
     * enough to make parsing and rendering take over a second, and send memory
     * usage over 128 megs, back in the old DOM API. So we blacklist every field
     * we don't strictly need and hope that that's good enough.
     *
     * In retrospect -- this is probably why print_r does the same
     *
     * @psalm-var array<string, true>
     */
    public static array $blacklist = [
        'parentNode' => true,
        'firstChild' => true,
        'lastChild' => true,
        'previousSibling' => true,
        'nextSibling' => true,
        'firstElementChild' => true,
        'lastElementChild' => true,
        'parentElement' => true,
        'previousElementSibling' => true,
        'nextElementSibling' => true,
        'ownerDocument' => true,
    ];

    /**
     * Show all properties and methods.
     */
    public static bool $verbose = false;

    protected ClassMethodsPlugin $methods_plugin;
    protected ClassStaticsPlugin $statics_plugin;

    public function __construct(Parser $parser)
    {
        parent::__construct($parser);

        $this->methods_plugin = new ClassMethodsPlugin($parser);
        $this->statics_plugin = new ClassStaticsPlugin($parser);
    }

    public function setParser(Parser $p): void
    {
        parent::setParser($p);

        $this->methods_plugin->setParser($p);
        $this->statics_plugin->setParser($p);
    }

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        if (!KINT_PHP84) {
            return Parser::TRIGGER_NONE;
        }

        return Parser::TRIGGER_BEGIN;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        $parser = $this->getParser();

        // Attributes and chardata (Which is parent of comments and text
        // nodes) don't need children or attributes of their own
        if ($var instanceof Attr || $var instanceof CharacterData) {
            $parser->haltParse();

            $new = new InstanceValue($o->name, \get_class($var), \spl_object_hash($var), \spl_object_id($var));
            $new->transplant($o);
            $o = $this->parseText($var, $new);

            return;
        }

        if ($var instanceof NamedNodeMap || $var instanceof NodeList) {
            $parser->haltParse();

            $new = new InstanceValue($o->name, \get_class($var), \spl_object_hash($var), \spl_object_id($var));
            $new->transplant($o);
            $o = $this->parseList($var, $new);

            return;
        }

        if ($var instanceof Node) {
            $parser->haltParse();

            $new = new InstanceValue($o->name, \get_class($var), \spl_object_hash($var), \spl_object_id($var));
            $new->transplant($o);
            $o = $this->parseNode($var, $new);

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

        $parser = $this->getParser();
        $value = $var->{$prop};

        if (\is_scalar($value)) {
            return $parser->parse($value, $base_obj);
        }

        $base_obj->hints['omit_spl_id'] = true;

        if (isset(self::$blacklist[$prop])) {
            $b = new InstanceValue($base_obj->name, \get_class($value), \spl_object_hash($value), \spl_object_id($value));
            $b->transplant($base_obj);
            $b->type = 'object';
            $b->hints['blacklist'] = true;

            return $b;
        }

        if ($value instanceof Attr || $value instanceof CharacterData || $value instanceof NamedNodeMap || $value instanceof NodeList || $value instanceof Node) {
            $base_obj->type = 'object';

            $this->parse($value, $base_obj, Parser::TRIGGER_BEGIN);

            return $base_obj;
        }

        // Shouldn't ever happen
        return $parser->parse($value, $base_obj); // @codeCoverageIgnore
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
    private function parseList($var, InstanceValue $o): Value
    {
        $o->size = $var->length;

        $parser = $this->getParser();

        if ($var instanceof NodeList) {
            $pdepth = $parser->getDepthLimit();

            // Depth limit
            // Make empty iterator representation since we need it to point out depth limits
            if ($pdepth && $o->depth >= $pdepth) {
                $o->hints['depth_limit'] = true;

                return $o;
            }
        }

        if (self::$verbose) {
            $this->methods_plugin->parse($var, $o, Parser::TRIGGER_SUCCESS);
            $this->statics_plugin->parse($var, $o, Parser::TRIGGER_SUCCESS);
        }

        $r = new Representation('Iterator');
        $r->contents = [];
        $o->addRepresentation($r, 0);
        $o->hints['iterator_primary'] = true;

        if (0 === $o->size) {
            return $o;
        }

        foreach ($var as $key => $item) {
            if ($var instanceof NamedNodeMap) {
                $base_obj = new Value($item->nodeName);
                $base_obj->depth = $o->depth + 1;
                $base_obj->hints['omit_spl_id'] = true;
                if (null !== $o->access_path) {
                    $base_obj->access_path = $o->access_path.'['.\var_export($item->nodeName, true).']';
                }

                $r->contents[] = $parser->parse($item, $base_obj);
            } else { // NodeList
                $base_obj = new InstanceValue($item->nodeName, \get_class($item), \spl_object_hash($item), \spl_object_id($item));
                $base_obj->depth = $o->depth + 1;
                $base_obj->hints['omit_spl_id'] = true;
                if (null !== $o->access_path) {
                    $base_obj->access_path = $o->access_path.'['.\var_export($key, true).']';
                }

                if ($item instanceof HTMLElement) {
                    $base_obj->name = $item->localName;
                }

                $this->parse($item, $base_obj, Parser::TRIGGER_BEGIN);
                $r->contents[] = $base_obj;
            }
        }

        return $o;
    }

    private function parseNode(Node $var, InstanceValue $o): Value
    {
        $pdepth = $this->getParser()->getDepthLimit();

        if ($pdepth && $o->depth >= $pdepth) {
            $o->hints['depth_limit'] = true;

            return $o;
        }

        if (self::$verbose) {
            if ($var instanceof Element) {
                $known_properties = self::ELEMENT_PROPS + self::NODE_PROPS;
            } else {
                $known_properties = self::NODE_PROPS;
            }

            if ($var instanceof Document) {
                $known_properties['textContent'] = true;
            }

            $this->methods_plugin->parse($var, $o, Parser::TRIGGER_SUCCESS);
            $this->statics_plugin->parse($var, $o, Parser::TRIGGER_SUCCESS);

            if (!$o instanceof InstanceValue) {
                throw new LogicException('ClassMethodsPlugin and/or ClassStaticsPlugin changed InstanceValue to Value'); // @codeCoverageIgnore
            }
        } else {
            $known_properties = [
                'childNodes' => true,
                'nodeValue' => false,
            ];

            if ($var instanceof Element) {
                $known_properties = ['attributes' => true] + $known_properties;
            }
        }

        if ($var instanceof Document || $var instanceof Element) {
            $known_properties['nodeValue'] = true;
        }

        $o->value = new Representation('Properties');
        $o->value->contents = [];

        if (self::$verbose) {
            $o->addRepresentation($o->value, 0);
        }

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
                if (!$prop_obj instanceof InstanceValue) {
                    throw new LogicException('childNodes property parsed incorrectly'); // @codeCoverageIgnore
                }
                $c = self::getChildrenRepresentation($prop_obj);
            } elseif ('attributes' === $prop) {
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
            if (\is_array($c->contents)) {
                $o->size = \count($c->contents);
            } elseif ($c->contents instanceof Value) {
                $o->size = $c->contents->size;
            }

            if (0 !== $o->size) {
                $o->addRepresentation($c, 0);
            }
        }

        return $o;
    }

    private static function getChildrenRepresentation(InstanceValue $property): Representation
    {
        $c = new Representation('Children');
        $c->implicit_label = true;
        $c->contents = [];

        if (isset($property->hints['depth_limit'])) {
            $stub = clone $property;
            // We don't want to check for empty strings as children
            if (0 !== $stub->size) {
                $stub->size = null;
            }
            $c->contents = $stub;
        } else {
            $iter = $property->getRepresentation('iterator');
            $contents = [];
            if (\is_array($iter->contents ?? null)) {
                /**
                 * @psalm-var Value[] $contents
                 * Psalm bug #11055
                 */
                $contents = $iter->contents;
            }

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

        return $c;
    }
}
