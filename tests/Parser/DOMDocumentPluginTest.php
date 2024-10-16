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

namespace Kint\Test\Parser;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Kint\Parser\AbstractPlugin;
use Kint\Parser\ClassMethodsPlugin;
use Kint\Parser\ClassStaticsPlugin;
use Kint\Parser\DOMDocumentPlugin;
use Kint\Parser\IteratorPlugin;
use Kint\Parser\Parser;
use Kint\Test\KintTestCase;
use Kint\Zval\BlobValue;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
use ReflectionClass;

/**
 * @coversNothing
 */
class DOMDocumentPluginTest extends KintTestCase
{
    public const TEST_XML = <<<END
        <?xml version="1.0" encoding="UTF-8"?>
        <x viewBox="0 0 30 150">
            <g stroke-width="2" fill="#FFF">
                <inner>Text</inner>
            </g>
            <g />
            String value
            <wrap>
                <wrap><text>String element</text></wrap>
                <not-php-compatible also-not="php-compatible" />
                <value type="string">Contents</value>
            </wrap>
            <both attribs="exist"><![CDATA[And string]]></both>
        </x>
        END;

    public const TEST_XML_NS = <<<END
        <?xml version="1.0" encoding="UTF-8"?>
        <x xmlns="http://localhost/" xmlns:test="http://localhost/test" viewBox="0 0 30 150">
            <test:g stroke-width="2" fill="#FFF">
                <inner>Text</inner>
            </test:g>
            <g xmlns="http://localhost/test" />
            String value
            <both attribs="base" test:attribs="exists"><![CDATA[And string]]></both>
        </x>
        END;

    public const TEST_HTML = <<<END
        <!DOCTYPE html>
        <body>
            <strong class="text">Hello world</strong>
            <DIV namespaces="allowed" />
        </body>
        END;

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::__construct
     * @covers \Kint\Parser\DOMDocumentPlugin::setParser
     */
    public function testConstruct()
    {
        $p = new Parser();
        $d = new DOMDocumentPlugin($p);

        $reflector = new ReflectionClass($d);
        $mprop = $reflector->getProperty('methods_plugin');
        $mprop->setAccessible(true);
        $m = $mprop->getValue($d);
        $this->assertInstanceOf(ClassMethodsPlugin::class, $m);
        $sprop = $reflector->getProperty('statics_plugin');
        $sprop->setAccessible(true);
        $s = $sprop->getValue($d);
        $this->assertInstanceOf(ClassStaticsPlugin::class, $s);

        $reflector = new ReflectionClass(AbstractPlugin::class);
        $aparser = $reflector->getProperty('parser');
        $aparser->setAccessible(true);

        $p = new Parser();
        $this->assertNotSame($p, $aparser->getValue($m));
        $this->assertNotSame($p, $aparser->getValue($s));
        $d->setParser($p);
        $this->assertSame($p, $aparser->getValue($m));
        $this->assertSame($p, $aparser->getValue($s));
    }

    public function testPropConsts()
    {
        if (!KINT_PHP81) {
            $this->markTestSkipped('No DOMDocument property reflection below 8.1');
        }

        $writable_props = ['id', 'className', 'prefix', 'nodeValue', 'textContent'];

        $props = [];
        $r = new ReflectionClass(DOMNode::class);
        foreach ($r->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $props[$prop->name] = !\in_array($prop->name, $writable_props, true);
        }

        $const_props = DOMDocumentPlugin::NODE_PROPS;
        foreach (DOMDocumentPlugin::ELEMENT_VERSIONS as $key => $val) {
            if (false === $val) {
                unset($const_props[$key]);
            }
        }
        $this->assertSame($props, $const_props);

        $props = [];
        $r = new ReflectionClass(DOMElement::class);
        foreach ($r->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $props[$prop->name] = !\in_array($prop->name, $writable_props, true);
        }

        $const_props = DOMDocumentPlugin::NODE_PROPS + DOMDocumentPlugin::ELEMENT_PROPS;
        foreach (DOMDocumentPlugin::ELEMENT_VERSIONS as $key => $val) {
            if (false === $val) {
                unset($const_props[$key]);
            }
        }

        $this->assertSame($props, $const_props);
    }

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::parseBegin
     * @covers \Kint\Parser\DOMDocumentPlugin::parseText
     * @covers \Kint\Parser\DOMDocumentPlugin::parseList
     * @covers \Kint\Parser\DOMDocumentPlugin::parseNode
     * @covers \Kint\Parser\DOMDocumentPlugin::parseProperty
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildrenRepresentation
     */
    public function testParseXML()
    {
        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DOMDocumentPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = new DOMDocument();
        $v->loadXML(self::TEST_XML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        DOMDocumentPlugin::$verbose = false;
        $o = $p->parse($v, clone $b);

        $this->basicAssertionsXml($o, false);
    }

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::parseBegin
     * @covers \Kint\Parser\DOMDocumentPlugin::parseText
     * @covers \Kint\Parser\DOMDocumentPlugin::parseList
     * @covers \Kint\Parser\DOMDocumentPlugin::parseNode
     * @covers \Kint\Parser\DOMDocumentPlugin::parseProperty
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildrenRepresentation
     */
    public function testParseXMLVerbose()
    {
        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DOMDocumentPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = new DOMDocument();
        $v->loadXML(self::TEST_XML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';
        DOMDocumentPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $this->basicAssertionsXml($o, true);

        $found_props = [];
        foreach ($o->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertCount(1, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertArrayHasKey('blacklist', $found_props['firstChild']->hints);
        $this->assertSame(DOMElement::class, $found_props['firstChild']->classname);
        $this->assertArrayHasKey('blacklist', $found_props['lastChild']->hints);
        $this->assertSame(DOMElement::class, $found_props['lastChild']->classname);

        $root_node = \reset($found_props['childNodes']->getRepresentation('iterator')->contents);

        $root_props = [];
        foreach ($root_node->value->contents as $val) {
            $root_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('blacklist', $root_props['ownerDocument']->hints);
        $this->assertSame(DOMDocument::class, $root_props['ownerDocument']->classname);
        $this->assertArrayHasKey('blacklist', $root_props['parentNode']->hints);
        $this->assertSame(DOMDocument::class, $root_props['parentNode']->classname);
        if (KINT_PHP80) {
            $this->assertArrayHasKey('blacklist', $root_props['firstElementChild']->hints);
            $this->assertSame(DOMElement::class, $root_props['firstElementChild']->classname);
            $this->assertArrayHasKey('blacklist', $root_props['lastElementChild']->hints);
            $this->assertSame(DOMElement::class, $root_props['lastElementChild']->classname);
        }
        $this->assertArrayHasKey('blacklist', $root_props['firstChild']->hints);
        $this->assertSame(DOMText::class, $root_props['firstChild']->classname);
        $this->assertArrayHasKey('blacklist', $root_props['lastChild']->hints);
        $this->assertSame(DOMText::class, $root_props['lastChild']->classname);
    }

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::parseBegin
     * @covers \Kint\Parser\DOMDocumentPlugin::parseText
     * @covers \Kint\Parser\DOMDocumentPlugin::parseList
     * @covers \Kint\Parser\DOMDocumentPlugin::parseNode
     * @covers \Kint\Parser\DOMDocumentPlugin::parseProperty
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildrenRepresentation
     */
    public function testNamespaces()
    {
        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DOMDocumentPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = new DOMDocument();
        $v->loadXML(self::TEST_XML_NS);
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        DOMDocumentPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $root = $o->getRepresentation('children')->contents[0];
        $this->assertSame('$v->childNodes[0]', $root->getContext()->getAccessPath());

        $root_props = [];
        foreach ($root->value->contents as $val) {
            $root_props[$val->getContext()->getName()] = $val;
        }

        $this->assertSame('x', $root_props['tagName']->value->contents);
        $this->assertSame('x', $root_props['localName']->value->contents);
        $this->assertSame('http://localhost/', $root_props['namespaceURI']->value->contents);
        $this->assertSame('$v->childNodes[0]->namespaceURI', $root_props['namespaceURI']->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]->attributes', $root_props['attributes']->getContext()->getAccessPath());
        $this->assertSame(1, $root_props['attributes']->size);

        $this->assertEquals(
            $root_props['attributes']->getRepresentation('iterator')->contents,
            $root->getRepresentation('attributes')->contents
        );

        $attribs = [];
        foreach ($root->getRepresentation('attributes')->contents as $val) {
            $attribs[$val->getContext()->getName()] = $val;
        }

        $this->assertCount(1, $attribs);

        $this->assertSame('0 0 30 150', $attribs['viewBox']->value->contents);
        $this->assertSame('$v->childNodes[0]->attributes[\'viewBox\']->nodeValue', $attribs['viewBox']->getContext()->getAccessPath());

        $g = $root->getRepresentation('children')->contents[0];
        $this->assertSame('test:g', $g->getDisplayName());
        $this->assertSame('$v->childNodes[0]->childNodes[1]', $g->getContext()->getAccessPath());

        $gprops = [];
        foreach ($g->value->contents as $val) {
            $gprops[$val->getContext()->getName()] = $val;
        }

        $this->assertSame('test:g', $gprops['tagName']->value->contents);
        $this->assertSame('g', $gprops['localName']->value->contents);
        $this->assertSame('http://localhost/test', $gprops['namespaceURI']->value->contents);
        $this->assertSame('$v->childNodes[0]->childNodes[1]->namespaceURI', $gprops['namespaceURI']->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]->childNodes[1]->attributes', $gprops['attributes']->getContext()->getAccessPath());
        $this->assertSame(2, $gprops['attributes']->size);

        $g = $root->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g->getDisplayName());
        $this->assertSame('$v->childNodes[0]->childNodes[3]', $g->getContext()->getAccessPath());

        $gprops = [];
        foreach ($g->value->contents as $val) {
            $gprops[$val->getContext()->getName()] = $val;
        }

        $this->assertSame('g', $gprops['tagName']->value->contents);
        $this->assertSame('g', $gprops['localName']->value->contents);
        $this->assertSame('http://localhost/test', $gprops['namespaceURI']->value->contents);
        $this->assertSame('$v->childNodes[0]->childNodes[3]->namespaceURI', $gprops['namespaceURI']->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]->childNodes[3]->attributes', $gprops['attributes']->getContext()->getAccessPath());
        $this->assertSame(0, $gprops['attributes']->size);

        $both = $root->getRepresentation('children')->contents[3];
        $this->assertSame('both', $both->getDisplayName());
        $this->assertSame('$v->childNodes[0]->childNodes[5]', $both->getContext()->getAccessPath());

        $attribs = [];
        foreach ($both->getRepresentation('attributes')->contents as $val) {
            $attribs[$val->getContext()->getName()] = $val;
        }

        $this->assertCount(2, $attribs);

        $this->assertSame('base', $attribs['attribs']->value->contents);
        $this->assertSame('$v->childNodes[0]->childNodes[5]->attributes[\'attribs\']->nodeValue', $attribs['attribs']->getContext()->getAccessPath());

        $this->assertSame('exists', $attribs['test:attribs']->value->contents);
        $this->assertSame('$v->childNodes[0]->childNodes[5]->attributes[\'test:attribs\']->nodeValue', $attribs['test:attribs']->getContext()->getAccessPath());
    }

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::parseBegin
     * @covers \Kint\Parser\DOMDocumentPlugin::parseText
     * @covers \Kint\Parser\DOMDocumentPlugin::parseList
     * @covers \Kint\Parser\DOMDocumentPlugin::parseNode
     * @covers \Kint\Parser\DOMDocumentPlugin::parseProperty
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildrenRepresentation
     */
    public function testDepthLimit()
    {
        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DOMDocumentPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = new DOMDocument();
        $v->loadXML(self::TEST_XML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';
        DOMDocumentPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $o_props = [];
        foreach ($o->value->contents as $val) {
            $o_props[$val->getContext()->getName()] = $val;
        }

        $x = $o->getRepresentation('children')->contents[0];
        $this->assertSame('x', $x->getDisplayName());
        $this->assertSame(5, $x->size);
        $this->assertArrayNotHasKey('depth_limit', $x->hints);
        $this->assertSame($x->value, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(Representation::class, $x->getRepresentation('constants'));
        } else {
            $this->assertNull($x->getRepresentation('constants'));
        }

        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertSame('g', $g1->getDisplayName());
        $this->assertSame(1, $g1->size);
        $this->assertArrayNotHasKey('depth_limit', $g1->hints);
        $this->assertSame($g1->value, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('constants'));
        } else {
            $this->assertNull($g1->getRepresentation('constants'));
        }

        $found_props = [];
        foreach ($g1->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayNotHasKey('depth_limit', $found_props['childNodes']->hints);

        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g2->getDisplayName());
        $this->assertSame(0, $g2->size);
        $this->assertArrayNotHasKey('depth_limit', $g2->hints);
        $this->assertSame($g2->value, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('constants'));
        } else {
            $this->assertNull($g2->getRepresentation('constants'));
        }

        $text = $x->getRepresentation('children')->contents[2];
        $this->assertSame('#text', $text->getDisplayName());
        $this->assertSame(22, $text->size);
        $this->assertArrayNotHasKey('depth_limit', $text->hints);
        $this->assertNotNull($text->value);
        $this->assertNull($text->getRepresentation('properties'));
        $this->assertNull($text->getRepresentation('methods'));
        $this->assertNull($text->getRepresentation('constants'));

        $wrap = $x->getRepresentation('children')->contents[3];
        $this->assertSame('wrap', $wrap->getDisplayName());
        $this->assertSame(3, $wrap->size);
        $this->assertArrayNotHasKey('depth_limit', $wrap->hints);
        $this->assertNotNull($wrap->value);
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('constants'));
        } else {
            $this->assertNull($wrap->getRepresentation('constants'));
        }

        $p->setDepthLimit(4);
        $o = $p->parse($v, clone $b);

        $x = $o->getRepresentation('children')->contents[0];
        $this->assertSame('x', $x->getDisplayName());
        $this->assertSame(5, $x->size);
        $this->assertArrayNotHasKey('depth_limit', $x->hints);
        $this->assertSame($x->value, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(Representation::class, $x->getRepresentation('constants'));
        } else {
            $this->assertNull($x->getRepresentation('constants'));
        }

        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertSame('g', $g1->getDisplayName());
        $this->assertNull($g1->size);
        $this->assertArrayHasKey('depth_limit', $g1->hints);
        $this->assertSame($g1->value, $g1->getRepresentation('properties'));
        $this->assertNull($g1->getRepresentation('properties'));
        $this->assertNull($g1->getRepresentation('attributes'));
        $this->assertNull($g1->getRepresentation('constants'));

        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g2->getDisplayName());
        $this->assertNull($g2->size);
        $this->assertArrayHasKey('depth_limit', $g2->hints);
        $this->assertSame($g2->value, $g2->getRepresentation('properties'));
        $this->assertNull($g2->getRepresentation('properties'));
        $this->assertNull($g2->getRepresentation('attributes'));
        $this->assertNull($g2->getRepresentation('constants'));

        $text = $x->getRepresentation('children')->contents[2];
        $this->assertSame('#text', $text->getDisplayName());
        $this->assertSame(22, $text->size);
        $this->assertArrayNotHasKey('depth_limit', $text->hints);
        $this->assertNotNull($text->value);
        $this->assertNull($text->getRepresentation('properties'));
        $this->assertNull($text->getRepresentation('attributes'));
        $this->assertNull($text->getRepresentation('constants'));

        $wrap = $x->getRepresentation('children')->contents[3];
        $this->assertSame('wrap', $wrap->getDisplayName());
        $this->assertNull($wrap->size);
        $this->assertArrayHasKey('depth_limit', $wrap->hints);
        $this->assertSame($wrap->value, $wrap->getRepresentation('properties'));
        $this->assertNull($wrap->getRepresentation('properties'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertNull($wrap->getRepresentation('constants'));

        $p->setDepthLimit(5);
        $o = $p->parse($v, clone $b);

        $x = $o->getRepresentation('children')->contents[0];
        $this->assertSame('x', $x->getDisplayName());
        $this->assertSame(5, $x->size);
        $this->assertArrayNotHasKey('depth_limit', $x->hints);
        $this->assertSame($x->value, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(Representation::class, $x->getRepresentation('constants'));
        } else {
            $this->assertNull($x->getRepresentation('constants'));
        }

        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertSame('g', $g1->getDisplayName());
        $this->assertNull($g1->size);
        $this->assertArrayNotHasKey('depth_limit', $g1->hints);
        $this->assertSame($g1->value, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('constants'));
        } else {
            $this->assertNull($g1->getRepresentation('constants'));
        }

        $found_props = [];
        foreach ($g1->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayNotHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('depth_limit', $found_props['childNodes']->hints);
        $this->assertArrayNotHasKey('depth_limit', $found_props['attributes']->hints);

        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g2->getDisplayName());
        $this->assertSame(0, $g2->size);
        $this->assertArrayNotHasKey('depth_limit', $g2->hints);
        $this->assertSame($g2->value, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('constants'));
        } else {
            $this->assertNull($g2->getRepresentation('constants'));
        }
    }

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::parseBegin
     * @covers \Kint\Parser\DOMDocumentPlugin::parseText
     * @covers \Kint\Parser\DOMDocumentPlugin::parseList
     * @covers \Kint\Parser\DOMDocumentPlugin::parseNode
     * @covers \Kint\Parser\DOMDocumentPlugin::parseProperty
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildrenRepresentation
     */
    public function testHTML()
    {
        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DOMDocumentPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = new DOMDocument();
        $v->loadXML(self::TEST_HTML);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        DOMDocumentPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $expected_props = $this->getExpectedPropsCount();

        $found_props = [];
        foreach ($o->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(0, $o->getContext()->getDepth());
        $this->assertSame(2, $o->size); // Node size should be the same as...
        $this->assertCount(2, $o->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame(2, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(2, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertNull($o->getRepresentation('attributes'));

        $this->assertInstanceOf(Representation::class, $o->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $o->getRepresentation('methods'));
        $this->assertCount(\count($found_props), $o->getRepresentation('properties')->contents);
        $this->assertCount($expected_props['NODE_PROPS'], $found_props);
        $this->assertSame(KINT_PHP81, $found_props['nodeType']->getContext()->readonly);
        $this->assertFalse($found_props['textContent']->getContext()->readonly);

        // body
        $body = $found_props['childNodes']->getRepresentation('iterator')->contents[1] ?? null;
        $this->assertNotNull($body);
        $this->assertSame('body', $body->getDisplayName());
        $this->assertSame('$v->childNodes[1]', $body->getContext()->getAccessPath());

        $found_props = [];
        foreach ($body->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('body', $found_props['nodeName']->value->contents);
        $this->assertSame('body', $found_props['localName']->value->contents);
        $this->assertSame('body', $found_props['tagName']->value->contents);

        // strong
        $strong = $body->getRepresentation('children')->contents[0];
        $this->assertNotNull($strong);
        $this->assertSame('$v->childNodes[1]->childNodes[1]', $strong->getContext()->getAccessPath());

        $found_props = [];
        foreach ($strong->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('strong', $found_props['nodeName']->value->contents);
        $this->assertSame('strong', $found_props['localName']->value->contents);
        $this->assertSame('strong', $found_props['tagName']->value->contents);

        $attributes = $strong->getRepresentation('attributes');
        $this->assertInstanceOf(Representation::class, $attributes);

        $attrib = $attributes->contents[0];
        $this->assertInstanceOf(BlobValue::class, $attrib);
        $this->assertSame('class', $attrib->getDisplayName());
        $this->assertSame('$v->childNodes[1]->childNodes[1]->attributes[\'class\']->nodeValue', $attrib->getContext()->getAccessPath());
        $this->assertSame('text', $attrib->value->contents);

        if (KINT_PHP83) {
            $this->assertSame('string', $found_props['className']->type);
            $this->assertSame('text', $found_props['className']->value->contents);
        } else {
            $this->assertArrayNotHasKey('className', $found_props);
        }

        // div
        $div = $body->getRepresentation('children')->contents[1];
        $this->assertNotNull($div);
        $this->assertSame('$v->childNodes[1]->childNodes[3]', $div->getContext()->getAccessPath());

        $found_props = [];
        foreach ($div->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('DIV', $found_props['nodeName']->value->contents);
        $this->assertSame('DIV', $found_props['localName']->value->contents);
        $this->assertSame('DIV', $found_props['tagName']->value->contents);

        $attributes = $div->getRepresentation('attributes');
        $this->assertInstanceOf(Representation::class, $attributes);

        $attrib = $attributes->contents[0];
        $this->assertInstanceOf(BlobValue::class, $attrib);
        $this->assertSame('namespaces', $attrib->getDisplayName());
        $this->assertSame('$v->childNodes[1]->childNodes[3]->attributes[\'namespaces\']->nodeValue', $attrib->getContext()->getAccessPath());
        $this->assertSame('allowed', $attrib->value->contents);
    }

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::parseBegin
     */
    public function testBadParse()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $ddp = new DOMDocumentPlugin($p);

        $p->addPlugin($ddp);

        $o = $ddp->parseBegin($v, $b, Parser::TRIGGER_BEGIN);

        $this->assertNull($o);
    }

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::getTriggers
     * @covers \Kint\Parser\DOMDocumentPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new DOMDocumentPlugin($this->createStub(Parser::class));

        $this->assertSame(['object'], $p->getTypes());
        $this->assertSame(Parser::TRIGGER_BEGIN, $p->getTriggers());
    }

    protected function basicAssertionsXml(Value $o, bool $verbose)
    {
        $expected_props = $this->getExpectedPropsCount();

        $found_props = [];
        foreach ($o->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(0, $o->getContext()->getDepth());
        $this->assertArrayNotHasKey('omit_spl_id', $o->hints);
        $this->assertSame(1, $o->size); // Node size should be the same as...
        $this->assertCount(1, $o->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame(1, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(1, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('omit_spl_id', $found_props['childNodes']->hints);
        $this->assertNull($o->getRepresentation('attributes'));

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('methods'));
            if (KINT_PHP84) {
                $this->assertInstanceOf(Representation::class, $o->getRepresentation('constants'));
            } else {
                $this->assertNull($o->getRepresentation('constants'));
            }
            $this->assertCount(\count($found_props), $o->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['NODE_PROPS'], $found_props);
            $this->assertSame(KINT_PHP81, $found_props['nodeType']->getContext()->readonly);
            $this->assertFalse($found_props['textContent']->getContext()->readonly);
        } else {
            $this->assertNull($o->getRepresentation('properties'));
            $this->assertNull($o->getRepresentation('methods'));
            $this->assertCount(3, $found_props);
        }

        // x
        $x = $found_props['childNodes']->getRepresentation('iterator')->contents[0] ?? null;

        $this->assertNotNull($x);
        $this->assertSame('$v->childNodes[0]', $x->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]', $o->getRepresentation('children')->contents[0]->getContext()->getAccessPath());

        $found_props = [];
        foreach ($x->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(2, $x->getContext()->getDepth());
        $this->assertArrayHasKey('omit_spl_id', $x->hints);
        $this->assertSame(5, $x->size); // Node size should be the same as...
        $this->assertCount(5, $x->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame(9, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(9, $found_props['childNodes']->getRepresentation('iterator')->contents); // Actual elements of childNodes
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('omit_spl_id', $found_props['childNodes']->hints);

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
            if (KINT_PHP84) {
                $this->assertInstanceOf(Representation::class, $x->getRepresentation('constants'));
            } else {
                $this->assertNull($x->getRepresentation('constants'));
            }
            $this->assertCount(\count($found_props), $x->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
            $this->assertSame(KINT_PHP81, $found_props['nodeType']->getContext()->readonly);
            $this->assertFalse($found_props['textContent']->getContext()->readonly);
        } else {
            $this->assertNull($x->getRepresentation('properties'));
            $this->assertNull($x->getRepresentation('methods'));
            $this->assertNull($x->getRepresentation('constants'));
            $this->assertCount(3, $found_props);
        }

        // x attributes
        $attributes = $x->getRepresentation('attributes');
        $this->assertInstanceOf(Representation::class, $attributes);

        $attrib = $attributes->contents[0];
        $this->assertInstanceOf(BlobValue::class, $attrib);
        $this->assertSame('viewBox', $attrib->getDisplayName());
        $this->assertSame('$v->childNodes[0]->attributes[\'viewBox\']->nodeValue', $attrib->getContext()->getAccessPath());
        $this->assertSame('0 0 30 150', $attrib->value->contents);

        // g1
        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertNotNull($g1);
        $this->assertSame('$v->childNodes[0]->childNodes[1]', $g1->getContext()->getAccessPath());

        $found_props = [];
        foreach ($g1->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(4, $g1->getContext()->getDepth());
        $this->assertArrayHasKey('omit_spl_id', $g1->hints);
        $this->assertSame(1, $g1->size); // Node size should be the same as...
        $this->assertCount(1, $g1->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame(3, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(3, $found_props['childNodes']->getRepresentation('iterator')->contents); // Actual elements of childNodes
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('omit_spl_id', $found_props['childNodes']->hints);

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
            if (KINT_PHP84) {
                $this->assertInstanceOf(Representation::class, $g1->getRepresentation('constants'));
            } else {
                $this->assertNull($g1->getRepresentation('constants'));
            }
            $this->assertCount(\count($found_props), $g1->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
            $this->assertSame(KINT_PHP81, $found_props['nodeType']->getContext()->readonly);
            $this->assertFalse($found_props['textContent']->getContext()->readonly);
        } else {
            $this->assertNull($g1->getRepresentation('properties'));
            $this->assertNull($g1->getRepresentation('methods'));
            $this->assertNull($g1->getRepresentation('constants'));
            $this->assertCount(3, $found_props);
        }

        // g2
        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertNotNull($g2);
        $this->assertSame('$v->childNodes[0]->childNodes[3]', $g2->getContext()->getAccessPath());

        $found_props = [];
        foreach ($g2->value->contents as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(4, $g2->getContext()->getDepth());
        $this->assertArrayHasKey('omit_spl_id', $g2->hints);
        $this->assertSame(0, $g2->size); // Node size should be the same as...
        $this->assertNull($g2->getRepresentation('children')); // Children with empty space removed
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame(0, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(0, $found_props['childNodes']->getRepresentation('iterator')->contents); // Actual elements of childNodes
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('omit_spl_id', $found_props['childNodes']->hints);

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
            if (KINT_PHP84) {
                $this->assertInstanceOf(Representation::class, $g2->getRepresentation('constants'));
            } else {
                $this->assertNull($g2->getRepresentation('constants'));
            }
            $this->assertCount(\count($found_props), $g2->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
            $this->assertSame(KINT_PHP81, $found_props['nodeType']->getContext()->readonly);
            $this->assertFalse($found_props['textContent']->getContext()->readonly);
        } else {
            $this->assertNull($g2->getRepresentation('properties'));
            $this->assertNull($g2->getRepresentation('methods'));
            $this->assertNull($g2->getRepresentation('constants'));
            $this->assertCount(3, $found_props);
        }

        // not-php-compatible
        $incomp = $x->getRepresentation('children')->contents[3]->getRepresentation('children')->contents[1];

        $this->assertSame('$v->childNodes[0]->childNodes[5]->childNodes[3]', $incomp->getContext()->getAccessPath());
        $this->assertSame(
            '$v->childNodes[0]->childNodes[5]->childNodes[3]->attributes[\'also-not\']->nodeValue',
            $incomp->getRepresentation('attributes')->contents[0]->getContext()->getAccessPath()
        );
        $this->assertSame('php-compatible', $incomp->getRepresentation('attributes')->contents[0]->value->contents);
    }

    private function getExpectedPropsCount()
    {
        $expected_props = [
            'NODE_PROPS' => 18,
            'ELEMENT_PROPS' => 2,
        ];

        if (KINT_PHP80) {
            $expected_props['ELEMENT_PROPS'] += 5;
        }

        if (KINT_PHP81) {
            $expected_props['NODE_PROPS'] -= 2;
        }

        if (KINT_PHP83) {
            $expected_props['NODE_PROPS'] += 2;
            $expected_props['ELEMENT_PROPS'] += 2;
        }

        $expected_props['ELEMENT_PROPS'] += $expected_props['NODE_PROPS'];

        return $expected_props;
    }
}
