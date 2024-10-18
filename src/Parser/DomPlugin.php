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
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\ContextInterface;
use Kint\Value\Context\PropertyContext;
use Kint\Value\DomNodeListValue;
use Kint\Value\DomNodeValue;
use Kint\Value\FixedWidthValue;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\Representation;
use Kint\Value\StringValue;
use LogicException;

/**
 * This is for parsing the new PHP 8.4 Dom tools which are spec-compliant and
 * support HTML. Most of this code originated in DOMDocumentPlugin and hopefully
 * we can one day bump the min version to 8.4 and replace it with importLegacyNode.
 */
class DomPlugin extends AbstractPlugin implements PluginBeginInterface
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

    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
    {
        // Attributes and chardata (Which is parent of comments and text
        // nodes) don't need children or attributes of their own
        if ($var instanceof Attr || $var instanceof CharacterData) {
            return $this->parseText($var, $c);
        }

        if ($var instanceof NamedNodeMap || $var instanceof NodeList) {
            return $this->parseList($var, $c);
        }

        if ($var instanceof Node) {
            return $this->parseNode($var, $c);
        }

        return null;
    }

    protected function parseProperty(Node $var, string $prop, ContextInterface $c): AbstractValue
    {
        if (!isset($var->{$prop})) {
            return new FixedWidthValue($c, null);
        }

        $parser = $this->getParser();
        $value = $var->{$prop};

        if (\is_scalar($value)) {
            return $parser->parse($value, $c);
        }

        if (isset(self::$blacklist[$prop])) {
            $b = new InstanceValue($c, \get_class($value), \spl_object_hash($value), \spl_object_id($value));
            $b->addHint('omit_spl_id');
            $b->addHint('blacklist');

            return $b;
        }

        if ($value instanceof Attr || $value instanceof CharacterData || $value instanceof NamedNodeMap || $value instanceof NodeList || $value instanceof Node) {
            $out = $this->parseBegin($value, $c);
        }

        if (!isset($out)) {
            // Shouldn't ever happen
            $out = $parser->parse($value, $c); // @codeCoverageIgnore
        }

        $out->addHint('omit_spl_id');

        return $out;
    }

    /**
     * @param Attr|CharacterData $var
     */
    private function parseText($var, ContextInterface $c): AbstractValue
    {
        if ($c instanceof BaseContext && null !== $c->access_path) {
            $c->access_path .= '->nodeValue';
        }

        return $this->parseProperty($var, 'nodeValue', $c);
    }

    /**
     * @param NamedNodeMap|NodeList $var
     */
    private function parseList($var, ContextInterface $c): InstanceValue
    {
        if ($var instanceof NodeList) {
            $v = new DomNodeListValue($c, $var);
        } else {
            $v = new InstanceValue($c, \get_class($var), \spl_object_hash($var), \spl_object_id($var));
        }

        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();

        // Depth limit
        // Use empty iterator representation since we need it to point out depth limits
        if ($var instanceof NodeList && $pdepth && $c->getDepth() >= $pdepth) {
            $v->addHint('depth_limit');

            return $v;
        }

        if (self::$verbose) {
            $v = $this->methods_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
            $v = $this->statics_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
        }

        if (0 === $var->length) {
            $v->setChildren([]);

            return $v;
        }

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();
        $contents = [];

        foreach ($var as $key => $item) {
            $base_obj = new BaseContext($item->nodeName);
            $base_obj->depth = $cdepth + 1;

            if ($var instanceof NamedNodeMap) {
                if (null !== $ap) {
                    $base_obj->access_path = $ap.'['.\var_export($item->nodeName, true).']';
                }
            } else { // NodeList
                if (null !== $ap) {
                    $base_obj->access_path = $ap.'['.\var_export($key, true).']';
                }
            }

            if ($item instanceof HTMLElement) {
                $base_obj->name = $item->localName;
            }

            $item = $parser->parse($item, $base_obj);
            $item->addHint('omit_spl_id');

            $contents[] = $item;
        }

        $v->setChildren($contents);

        $r = new Representation('Iterator');
        $r->contents = $contents;
        $v->addRepresentation($r, 0);

        return $v;
    }

    private function parseNode(Node $var, ContextInterface $c): DomNodeValue
    {
        $class = \get_class($var);
        $pdepth = $this->getParser()->getDepthLimit();

        if ($pdepth && $c->getDepth() >= $pdepth) {
            $v = new DomNodeValue($c, $var);
            $v->addHint('depth_limit');

            return $v;
        }

        if (self::$verbose) {
            $known_properties = self::NODE_PROPS;
            if ($var instanceof Element) {
                $known_properties += self::ELEMENT_PROPS;
            }

            if ($var instanceof Document) {
                $known_properties['textContent'] = true;
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

        if ($var instanceof DocumentType && $c instanceof BaseContext && $c->name === $var->nodeName) {
            $c->name = '!DOCTYPE '.$c->name;
        }

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();

        $properties = [];
        $children = [];
        $attributes = [];

        foreach ($known_properties as $prop => $readonly) {
            $prop_c = new PropertyContext($prop, $class, ClassDeclaredContext::ACCESS_PUBLIC);
            $prop_c->depth = $cdepth + 1;
            $prop_c->readonly = KINT_PHP81 && $readonly;

            if (null !== $ap) {
                $prop_c->access_path = $ap.'->'.$prop;
            }

            $properties[] = $prop_obj = $this->parseProperty($var, $prop, $prop_c);

            if ('childNodes' === $prop) {
                if (!$prop_obj instanceof DomNodeListValue) {
                    throw new LogicException('childNodes property parsed incorrectly'); // @codeCoverageIgnore
                }
                $children = self::getChildren($prop_obj);
            } elseif ('attributes' === $prop) {
                $attributes = $prop_obj->getRepresentation('iterator')->contents ?? null;
                if (!\is_array($attributes)) {
                    $attributes = [];
                }
            }
        }

        $v = new DomNodeValue($c, $var);
        // If we're in text mode, we can see children through the childNodes property
        $v->setChildren($properties);

        if ($children) {
            $crep = new Representation('Children');
            $crep->implicit_label = true;
            $crep->contents = $children;

            $v->addRepresentation($crep);
        }

        if ($attributes) {
            $arep = new Representation('Attributes');
            $arep->contents = $attributes;
            $v->addRepresentation($arep);
        }

        if (self::$verbose) {
            $props = new Representation('Properties');
            $props->contents = $properties;
            $v->addRepresentation($props);

            $v = $this->methods_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
            $v = $this->statics_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
        }

        return $v;
    }

    /** @psalm-return list<AbstractValue> */
    private static function getChildren(DomNodeListValue $property): array
    {
        if (0 === $property->getLength()) {
            return [];
        }

        if ($property->hasHint('depth_limit')) {
            return [$property];
        }

        $list_items = $property->getChildren();

        if (null === $list_items) {
            // This is here for psalm but all DomNodeListValue should
            // either be depth_limit or have array children
            return []; // @codeCoverageIgnore
        }

        $children = [];

        foreach ($list_items as $node) {
            // Remove text nodes if theyre empty
            if ($node instanceof StringValue && '#text' === $node->getContext()->getName()) {
                /**
                 * @psalm-suppress InvalidArgument
                 * Psalm bug #11055
                 */
                if (\ctype_space($node->getValue()) || '' === $node->getValue()) {
                    continue;
                }
            }

            $children[] = $node;
        }

        return $children;
    }
}
