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
use Kint\Zval\BlobValue;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\Context\ClassDeclaredContext;
use Kint\Zval\Context\ContextInterface;
use Kint\Zval\Context\PropertyContext;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
use LogicException;

/**
 * Backported from DomPlugin, if comments are missing check there.
 *
 * @see DomPlugin
 */
class DOMDocumentPlugin extends AbstractPlugin implements PluginBeginInterface
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
        'parentElement' => KINT_PHP83 || !KINT_PHP81,
        'isConnected' => KINT_PHP83 || !KINT_PHP81,
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
        return Parser::TRIGGER_BEGIN;
    }

    public function parseBegin(&$var, ContextInterface $c): ?Value
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

    protected function parseProperty(Node $var, string $prop, ContextInterface $c): Value
    {
        if (!isset($var->{$prop})) {
            $v = new Value($c);
            $v->type = 'null';

            return $v;
        }

        $parser = $this->getParser();
        $value = $var->{$prop};

        if (\is_scalar($value)) {
            return $parser->parse($value, $c);
        }

        if (isset(self::$blacklist[$prop])) {
            $b = new InstanceValue($c, \get_class($value), \spl_object_hash($value), \spl_object_id($value));
            $b->hints['omit_spl_id'] = true;
            $b->hints['blacklist'] = true;

            return $b;
        }

        if ($value instanceof Attr || $value instanceof CharacterData || $value instanceof NamedNodeMap || $value instanceof NodeList || $value instanceof Node) {
            $out = $this->parseBegin($value, $c);
        }

        if (!isset($out)) {
            // Shouldn't ever happen
            $out = $parser->parse($value, $c); // @codeCoverageIgnore
        }

        $out->hints['omit_spl_id'] = true;

        return $out;
    }

    /**
     * @param Attr|CharacterData $var
     */
    private function parseText($var, ContextInterface $c): Value
    {
        if ($c instanceof BaseContext && null !== $c->access_path) {
            $c->access_path .= '->nodeValue';
        }

        return $this->parseProperty($var, 'nodeValue', $c);
    }

    /**
     * @param NamedNodeMap|NodeList $var
     */
    private function parseList($var, ContextInterface $c): Value
    {
        $v = new InstanceValue($c, \get_class($var), \spl_object_hash($var), \spl_object_id($var));
        $v->size = $var->length;

        $parser = $this->getParser();

        if ($var instanceof NodeList) {
            $pdepth = $parser->getDepthLimit();

            // Depth limit
            // Make empty iterator representation since we need it to point out depth limits
            if ($pdepth && $c->getDepth() >= $pdepth) {
                $v->hints['depth_limit'] = true;

                return $v;
            }
        }

        if (self::$verbose) {
            $v = $this->methods_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
            $v = $this->statics_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
        }

        $r = new Representation('Iterator');
        $r->contents = [];
        $v->addRepresentation($r, 0);
        $v->hints['iterator_primary'] = true;

        if (0 === $v->size) {
            return $v;
        }

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();

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

            $item = $parser->parse($item, $base_obj);
            $item->hints['omit_spl_id'] = true;

            $r->contents[] = $item;
        }

        return $v;
    }

    private function parseNode(Node $var, ContextInterface $c): Value
    {
        $v = new InstanceValue($c, \get_class($var), \spl_object_hash($var), \spl_object_id($var));

        $pdepth = $this->getParser()->getDepthLimit();

        if ($pdepth && $c->getDepth() >= $pdepth) {
            $v->hints['depth_limit'] = true;

            return $v;
        }

        if (self::$verbose) {
            $known_properties = self::NODE_PROPS;
            if ($var instanceof Element) {
                $known_properties += self::ELEMENT_PROPS;
            }

            foreach (self::ELEMENT_VERSIONS as $key => $val) {
                /**
                 * @psalm-suppress TypeDoesNotContainType
                 * Psalm bug #4509
                 */
                if (false === $val) {
                    unset($known_properties[$key]); // @codeCoverageIgnore
                }
            }

            $v = $this->methods_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);
            $v = $this->statics_plugin->parseComplete($var, $v, Parser::TRIGGER_SUCCESS);

            if (!$v instanceof InstanceValue) {
                throw new LogicException('ClassMethodsPlugin and/or ClassStaticsPlugin changed InstanceValue to Value'); // @codeCoverageIgnore
            }
        } else {
            $known_properties = [
                'nodeValue' => false,
                'childNodes' => true,
                'attributes' => true,
            ];
        }

        $v->value = new Representation('Properties');
        $v->value->contents = [];

        if (self::$verbose) {
            $v->addRepresentation($v->value, 0);
        }

        if ($var instanceof DocumentType && $c instanceof BaseContext && $c->name === $var->nodeName) {
            $c->name = '!DOCTYPE '.$c->name;
        }

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();

        $v->size = null;
        $crep = null;
        $arep = null;

        foreach ($known_properties as $prop => $readonly) {
            $prop_c = new PropertyContext($prop, $v->classname, ClassDeclaredContext::ACCESS_PUBLIC);
            $prop_c->depth = $cdepth + 1;
            $prop_c->readonly = KINT_PHP81 && $readonly;

            if (null !== $ap) {
                $prop_c->access_path = $ap.'->'.$prop;
            }

            $v->value->contents[] = $prop_obj = $this->parseProperty($var, $prop, $prop_c);

            if ('childNodes' === $prop) {
                if (!$prop_obj instanceof InstanceValue) {
                    throw new LogicException('childNodes property parsed incorrectly'); // @codeCoverageIgnore
                }
                $crep = self::getChildrenRepresentation($prop_obj);
            } elseif ('attributes' === $prop) {
                $arep = $prop_obj->getRepresentation('iterator');
            }
        }

        // We do this separately here so children representation shows
        // up before attributes regardless of supposed parameter order
        if ($arep) {
            $attributes = $arep->contents;
            $arep = new Representation('Attributes');
            $arep->contents = $attributes;
            $v->addRepresentation($arep, 0);
        }

        if ($crep) {
            if (\is_array($crep->contents)) {
                $v->size = \count($crep->contents);
            } elseif ($crep->contents instanceof Value) {
                $v->size = $crep->contents->size;
            }

            if (0 !== $v->size) {
                $v->addRepresentation($crep, 0);
            }
        }

        return $v;
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
                if ($node instanceof BlobValue && '#text' === $node->getContext()->getName()) {
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
