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
use DOMElement as Element;
use DOMNode as Node;
use DOMText as Text;
use Kint\Parser\AbstractPlugin;
use Kint\Parser\ClassMethodsPlugin;
use Kint\Parser\ClassStaticsPlugin;
use Kint\Parser\DOMDocumentPlugin;
use Kint\Parser\IteratorPlugin;
use Kint\Parser\Parser;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\DomNodeListValue;
use Kint\Value\DomNodeValue;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\StringValue;
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
        $r = new ReflectionClass(Node::class);
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
        $r = new ReflectionClass(Element::class);
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
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildren
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
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildren
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
        foreach ($o->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertCount(1, $found_props['childNodes']->getRepresentation('iterator')->getContents());
        $this->assertEquals(true, $found_props['firstChild']->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertSame(Element::class, $found_props['firstChild']->getClassName());
        $this->assertEquals(true, $found_props['lastChild']->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertSame(Element::class, $found_props['lastChild']->getClassName());

        $root_node = $found_props['childNodes']->getRepresentation('iterator')->getContents()[0];

        $root_props = [];
        foreach ($root_node->getChildren() as $val) {
            $root_props[$val->getContext()->getName()] = $val;
        }

        $this->assertEquals(true, $root_props['ownerDocument']->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertSame(DOMDocument::class, $root_props['ownerDocument']->getClassName());
        $this->assertEquals(true, $root_props['parentNode']->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertSame(DOMDocument::class, $root_props['parentNode']->getClassName());
        if (KINT_PHP80) {
            $this->assertEquals(true, $root_props['firstElementChild']->flags & AbstractValue::FLAG_BLACKLIST);
            $this->assertSame(Element::class, $root_props['firstElementChild']->getClassName());
            $this->assertEquals(true, $root_props['lastElementChild']->flags & AbstractValue::FLAG_BLACKLIST);
            $this->assertSame(Element::class, $root_props['lastElementChild']->getClassName());
        }
        $this->assertEquals(true, $root_props['firstChild']->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertSame(Text::class, $root_props['firstChild']->getClassName());
        $this->assertEquals(true, $root_props['lastChild']->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertSame(Text::class, $root_props['lastChild']->getClassName());
    }

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::parseBegin
     * @covers \Kint\Parser\DOMDocumentPlugin::parseText
     * @covers \Kint\Parser\DOMDocumentPlugin::parseList
     * @covers \Kint\Parser\DOMDocumentPlugin::parseNode
     * @covers \Kint\Parser\DOMDocumentPlugin::parseProperty
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildren
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

        $root = $o->getRepresentation('children')->getContents()[0];
        $this->assertSame('$v->childNodes[0]', $root->getContext()->getAccessPath());

        $root_props = [];
        foreach ($root->getChildren() as $val) {
            $root_props[$val->getContext()->getName()] = $val;
        }

        $this->assertSame('x', $root_props['tagName']->getValue());
        $this->assertSame('x', $root_props['localName']->getValue());
        $this->assertSame('http://localhost/', $root_props['namespaceURI']->getValue());
        $this->assertSame('$v->childNodes[0]->namespaceURI', $root_props['namespaceURI']->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]->attributes', $root_props['attributes']->getContext()->getAccessPath());
        $this->assertSame('1', $root_props['attributes']->getDisplaySize());

        $this->assertEquals(
            $root_props['attributes']->getRepresentation('iterator')->getContents(),
            $root->getRepresentation('attributes')->getContents()
        );

        $attribs = [];
        foreach ($root->getRepresentation('attributes')->getContents() as $val) {
            $attribs[$val->getContext()->getName()] = $val;
        }

        $this->assertCount(1, $attribs);

        $this->assertSame('0 0 30 150', $attribs['viewBox']->getValue());
        $this->assertSame('$v->childNodes[0]->attributes[\'viewBox\']->nodeValue', $attribs['viewBox']->getContext()->getAccessPath());

        $g = $root->getRepresentation('children')->getContents()[0];
        $this->assertSame('test:g', $g->getDisplayName());
        $this->assertSame('$v->childNodes[0]->childNodes[1]', $g->getContext()->getAccessPath());

        $gprops = [];
        foreach ($g->getChildren() as $val) {
            $gprops[$val->getContext()->getName()] = $val;
        }

        $this->assertSame('test:g', $gprops['tagName']->getValue());
        $this->assertSame('g', $gprops['localName']->getValue());
        $this->assertSame('http://localhost/test', $gprops['namespaceURI']->getValue());
        $this->assertSame('$v->childNodes[0]->childNodes[1]->namespaceURI', $gprops['namespaceURI']->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]->childNodes[1]->attributes', $gprops['attributes']->getContext()->getAccessPath());
        $this->assertSame('2', $gprops['attributes']->getDisplaySize());

        $g = $root->getRepresentation('children')->getContents()[1];
        $this->assertSame('g', $g->getDisplayName());
        $this->assertSame('$v->childNodes[0]->childNodes[3]', $g->getContext()->getAccessPath());

        $gprops = [];
        foreach ($g->getChildren() as $val) {
            $gprops[$val->getContext()->getName()] = $val;
        }

        $this->assertSame('g', $gprops['tagName']->getValue());
        $this->assertSame('g', $gprops['localName']->getValue());
        $this->assertSame('http://localhost/test', $gprops['namespaceURI']->getValue());
        $this->assertSame('$v->childNodes[0]->childNodes[3]->namespaceURI', $gprops['namespaceURI']->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]->childNodes[3]->attributes', $gprops['attributes']->getContext()->getAccessPath());
        $this->assertSame('0', $gprops['attributes']->getDisplaySize());

        $both = $root->getRepresentation('children')->getContents()[3];
        $this->assertSame('both', $both->getDisplayName());
        $this->assertSame('$v->childNodes[0]->childNodes[5]', $both->getContext()->getAccessPath());

        $attribs = [];
        foreach ($both->getRepresentation('attributes')->getContents() as $val) {
            $attribs[$val->getContext()->getName()] = $val;
        }

        $this->assertCount(2, $attribs);

        $this->assertSame('base', $attribs['attribs']->getValue());
        $this->assertSame('$v->childNodes[0]->childNodes[5]->attributes[\'attribs\']->nodeValue', $attribs['attribs']->getContext()->getAccessPath());

        $this->assertSame('exists', $attribs['test:attribs']->getValue());
        $this->assertSame('$v->childNodes[0]->childNodes[5]->attributes[\'test:attribs\']->nodeValue', $attribs['test:attribs']->getContext()->getAccessPath());
    }

    /**
     * @covers \Kint\Parser\DOMDocumentPlugin::parseBegin
     * @covers \Kint\Parser\DOMDocumentPlugin::parseText
     * @covers \Kint\Parser\DOMDocumentPlugin::parseList
     * @covers \Kint\Parser\DOMDocumentPlugin::parseNode
     * @covers \Kint\Parser\DOMDocumentPlugin::parseProperty
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildren
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
        foreach ($o->getChildren() as $val) {
            $o_props[$val->getContext()->getName()] = $val;
        }

        $x = $o->getRepresentation('children')->getContents()[0];
        $this->assertSame('x', $x->getDisplayName());
        $this->assertNull($x->getDisplaySize());
        $this->assertSame(\count($x->getChildren()), \count($x->getRepresentation('properties')->getContents()));
        $this->assertCount(5, $x->getRepresentation('children')->getContents());
        $this->assertEquals(false, $x->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('constants'));
        } else {
            $this->assertNull($x->getRepresentation('constants'));
        }

        $g1 = $x->getRepresentation('children')->getContents()[0];
        $this->assertSame('g', $g1->getDisplayName());
        $this->assertNull($g1->getDisplaySize());
        $this->assertSame(\count($g1->getChildren()), \count($g1->getRepresentation('properties')->getContents()));
        $this->assertCount(1, $g1->getRepresentation('children')->getContents());
        $this->assertEquals(false, $g1->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('constants'));
        } else {
            $this->assertNull($g1->getRepresentation('constants'));
        }

        $found_props = [];
        foreach ($g1->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertEquals(false, $found_props['childNodes']->flags & AbstractValue::FLAG_DEPTH_LIMIT);

        $g2 = $x->getRepresentation('children')->getContents()[1];
        $this->assertSame('g', $g2->getDisplayName());
        $this->assertNull($g2->getDisplaySize());
        $this->assertSame(\count($g2->getChildren()), \count($g2->getRepresentation('properties')->getContents()));
        $this->assertNull($g2->getRepresentation('children'));
        $this->assertEquals(false, $g2->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('constants'));
        } else {
            $this->assertNull($g2->getRepresentation('constants'));
        }

        $text = $x->getRepresentation('children')->getContents()[2];
        $this->assertInstanceOf(StringValue::class, $text);
        $this->assertSame('#text', $text->getDisplayName());
        $this->assertSame(22, $text->getLength());
        $this->assertEquals(false, $text->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNotNull($text->getValue());
        $this->assertNull($text->getRepresentation('properties'));
        $this->assertNull($text->getRepresentation('methods'));
        $this->assertNull($text->getRepresentation('constants'));

        $wrap = $x->getRepresentation('children')->getContents()[3];
        $this->assertSame('wrap', $wrap->getDisplayName());
        $this->assertNull($wrap->getDisplaySize());
        $this->assertSame(\count($wrap->getChildren()), \count($wrap->getRepresentation('properties')->getContents()));
        $this->assertCount(3, $wrap->getRepresentation('children')->getContents());
        $this->assertEquals(false, $wrap->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertInstanceOf(ContainerRepresentation::class, $wrap->getRepresentation('properties'));
        $this->assertInstanceOf(ContainerRepresentation::class, $wrap->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(ContainerRepresentation::class, $wrap->getRepresentation('constants'));
        } else {
            $this->assertNull($wrap->getRepresentation('constants'));
        }

        $p->setDepthLimit(4);
        $o = $p->parse($v, clone $b);

        $x = $o->getRepresentation('children')->getContents()[0];
        $this->assertSame('x', $x->getDisplayName());
        $this->assertNull($x->getDisplaySize());
        $this->assertSame(\count($x->getChildren()), \count($x->getRepresentation('properties')->getContents()));
        $this->assertCount(5, $x->getRepresentation('children')->getContents());
        $this->assertEquals(false, $x->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('constants'));
        } else {
            $this->assertNull($x->getRepresentation('constants'));
        }

        $g1 = $x->getRepresentation('children')->getContents()[0];
        $this->assertSame('g', $g1->getDisplayName());
        $this->assertNull($g1->getDisplaySize());
        $this->assertEquals(true, $g1->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($g1->getRepresentation('children'));
        $this->assertNull($g1->getRepresentation('properties'));
        $this->assertNull($g1->getRepresentation('attributes'));
        $this->assertNull($g1->getRepresentation('constants'));

        $g2 = $x->getRepresentation('children')->getContents()[1];
        $this->assertSame('g', $g2->getDisplayName());
        $this->assertNull($g2->getDisplaySize());
        $this->assertEquals(true, $g2->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($g2->getRepresentation('children'));
        $this->assertNull($g2->getRepresentation('properties'));
        $this->assertNull($g2->getRepresentation('attributes'));
        $this->assertNull($g2->getRepresentation('constants'));

        $text = $x->getRepresentation('children')->getContents()[2];
        $this->assertInstanceOf(StringValue::class, $text);
        $this->assertSame('#text', $text->getDisplayName());
        $this->assertSame(22, $text->getLength());
        $this->assertEquals(false, $text->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNotNull($text->getValue());
        $this->assertNull($text->getRepresentation('children'));
        $this->assertNull($text->getRepresentation('properties'));
        $this->assertNull($text->getRepresentation('attributes'));
        $this->assertNull($text->getRepresentation('constants'));

        $wrap = $x->getRepresentation('children')->getContents()[3];
        $this->assertSame('wrap', $wrap->getDisplayName());
        $this->assertNull($wrap->getDisplaySize());
        $this->assertEquals(true, $wrap->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($wrap->getRepresentation('children'));
        $this->assertNull($wrap->getRepresentation('properties'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertNull($wrap->getRepresentation('constants'));

        $p->setDepthLimit(5);
        $o = $p->parse($v, clone $b);

        $x = $o->getRepresentation('children')->getContents()[0];
        $this->assertSame('x', $x->getDisplayName());
        $this->assertNull($x->getDisplaySize());
        $this->assertSame(\count($x->getChildren()), \count($x->getRepresentation('properties')->getContents()));
        $this->assertCount(5, $x->getRepresentation('children')->getContents());
        $this->assertEquals(false, $x->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('constants'));
        } else {
            $this->assertNull($x->getRepresentation('constants'));
        }

        $g1 = $x->getRepresentation('children')->getContents()[0];
        $this->assertSame('g', $g1->getDisplayName());
        $this->assertNull($g1->getDisplaySize());
        $this->assertSame(\count($g1->getChildren()), \count($g1->getRepresentation('properties')->getContents()));
        $this->assertCount(1, $g1->getRepresentation('children')->getContents());
        $this->assertEquals(false, $g1->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('constants'));
        } else {
            $this->assertNull($g1->getRepresentation('constants'));
        }

        $found_props = [];
        foreach ($g1->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertEquals(true, $found_props['childNodes']->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertEquals(false, $found_props['attributes']->flags & AbstractValue::FLAG_DEPTH_LIMIT);

        $g2 = $x->getRepresentation('children')->getContents()[1];
        $this->assertSame('g', $g2->getDisplayName());
        $this->assertNull($g2->getDisplaySize());
        $this->assertSame(\count($g2->getChildren()), \count($g2->getRepresentation('properties')->getContents()));
        $this->assertEquals(false, $g2->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($g2->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('methods'));
        if (KINT_PHP84) {
            $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('constants'));
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
     * @covers \Kint\Parser\DOMDocumentPlugin::getChildren
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
        foreach ($o->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(0, $o->getContext()->getDepth());
        $this->assertNull($o->getDisplaySize());
        $this->assertCount(2, $o->getRepresentation('children')->getContents()); // Children with empty space removed
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame('2', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(2, $found_props['childNodes']->getChildren());
        $this->assertCount(2, $found_props['childNodes']->getRepresentation('iterator')->getContents());
        $this->assertEquals(true, $found_props['childNodes']->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($o->getRepresentation('attributes'));

        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('properties'));
        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('methods'));
        $this->assertCount(\count($found_props), $o->getRepresentation('properties')->getContents());
        $this->assertCount($expected_props['NODE_PROPS'], $found_props);
        $this->assertSame(KINT_PHP81, $found_props['nodeType']->getContext()->readonly);
        $this->assertFalse($found_props['textContent']->getContext()->readonly);

        // body
        $body = $found_props['childNodes']->getRepresentation('iterator')->getContents()[1];
        $this->assertNotNull($body);
        $this->assertSame('body', $body->getDisplayName());
        $this->assertSame('$v->childNodes[1]', $body->getContext()->getAccessPath());

        $found_props = [];
        foreach ($body->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('body', $found_props['nodeName']->getValue());
        $this->assertSame('body', $found_props['localName']->getValue());
        $this->assertSame('body', $found_props['tagName']->getValue());

        // strong
        $strong = $body->getRepresentation('children')->getContents()[0];
        $this->assertNotNull($strong);
        $this->assertSame('$v->childNodes[1]->childNodes[1]', $strong->getContext()->getAccessPath());

        $found_props = [];
        foreach ($strong->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('strong', $found_props['nodeName']->getValue());
        $this->assertSame('strong', $found_props['localName']->getValue());
        $this->assertSame('strong', $found_props['tagName']->getValue());

        $attributes = $strong->getRepresentation('attributes');
        $this->assertInstanceOf(ContainerRepresentation::class, $attributes);

        $attrib = $attributes->getContents()[0];
        $this->assertInstanceOf(StringValue::class, $attrib);
        $this->assertSame('class', $attrib->getDisplayName());
        $this->assertSame('$v->childNodes[1]->childNodes[1]->attributes[\'class\']->nodeValue', $attrib->getContext()->getAccessPath());
        $this->assertSame('text', $attrib->getValue());

        if (KINT_PHP83) {
            $this->assertInstanceOf(StringValue::class, $found_props['className']);
            $this->assertSame('text', $found_props['className']->getValue());
        } else {
            $this->assertArrayNotHasKey('className', $found_props);
        }

        // div
        $div = $body->getRepresentation('children')->getContents()[1];
        $this->assertNotNull($div);
        $this->assertSame('$v->childNodes[1]->childNodes[3]', $div->getContext()->getAccessPath());

        $found_props = [];
        foreach ($div->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('DIV', $found_props['nodeName']->getValue());
        $this->assertSame('DIV', $found_props['localName']->getValue());
        $this->assertSame('DIV', $found_props['tagName']->getValue());

        $attributes = $div->getRepresentation('attributes');
        $this->assertInstanceOf(ContainerRepresentation::class, $attributes);

        $attrib = $attributes->getContents()[0];
        $this->assertInstanceOf(StringValue::class, $attrib);
        $this->assertSame('namespaces', $attrib->getDisplayName());
        $this->assertSame('$v->childNodes[1]->childNodes[3]->attributes[\'namespaces\']->nodeValue', $attrib->getContext()->getAccessPath());
        $this->assertSame('allowed', $attrib->getValue());
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

    protected function basicAssertionsXml(AbstractValue $o, bool $verbose)
    {
        $expected_props = $this->getExpectedPropsCount();

        $found_props = [];
        foreach ($o->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertInstanceOf(DomNodeValue::class, $o);
        $this->assertSame(0, $o->getContext()->getDepth());
        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($o->getDisplaySize());
        $this->assertCount(1, $o->getRepresentation('children')->getContents()); // Children with empty space removed
        $this->assertInstanceOf(DomNodeListValue::class, $found_props['childNodes']);
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame('1', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(1, $found_props['childNodes']->getChildren());
        $this->assertCount(1, $found_props['childNodes']->getRepresentation('iterator')->getContents());
        $this->assertEquals(true, $found_props['childNodes']->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($o->getRepresentation('attributes'));

        if ($verbose) {
            $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('properties'));
            $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('methods'));
            if (KINT_PHP84) {
                $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('constants'));
            } else {
                $this->assertNull($o->getRepresentation('constants'));
            }
            $this->assertCount(\count($found_props), $o->getRepresentation('properties')->getContents());
            $this->assertCount($expected_props['NODE_PROPS'], $found_props);
            $this->assertSame(KINT_PHP81, $found_props['nodeType']->getContext()->readonly);

            // textContent always settable in DOMDocument
            $this->assertFalse($found_props['textContent']->getContext()->readonly);
        } else {
            $this->assertNull($o->getRepresentation('properties'));
            $this->assertNull($o->getRepresentation('methods'));
            $this->assertNull($o->getRepresentation('constants'));
            $this->assertCount(3, $found_props);
        }

        // x
        $x = $found_props['childNodes']->getRepresentation('iterator')->getContents()[0] ?? null;

        $this->assertNotNull($x);
        $this->assertSame('$v->childNodes[0]', $x->getContext()->getAccessPath());
        $this->assertSame('$v->childNodes[0]', $o->getRepresentation('children')->getContents()[0]->getContext()->getAccessPath());

        $found_props = [];
        foreach ($x->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertInstanceOf(DomNodeValue::class, $x);
        $this->assertSame(2, $x->getContext()->getDepth());
        $this->assertEquals(true, $x->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($x->getDisplaySize());
        $this->assertCount(5, $x->getRepresentation('children')->getContents()); // Children with empty space removed
        $this->assertInstanceOf(DomNodeListValue::class, $found_props['childNodes']);
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame('9', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(9, $found_props['childNodes']->getChildren());
        $this->assertCount(9, $found_props['childNodes']->getRepresentation('iterator')->getContents());
        $this->assertEquals(true, $found_props['childNodes']->flags & AbstractValue::FLAG_GENERATED);

        if ($verbose) {
            $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('properties'));
            $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('methods'));
            if (KINT_PHP84) {
                $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('constants'));
            } else {
                $this->assertNull($x->getRepresentation('constants'));
            }
            $this->assertCount(\count($found_props), $x->getRepresentation('properties')->getContents());
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
        $this->assertInstanceOf(ContainerRepresentation::class, $attributes);

        $attrib = $attributes->getContents()[0];
        $this->assertInstanceOf(StringValue::class, $attrib);
        $this->assertSame('viewBox', $attrib->getDisplayName());
        $this->assertSame('$v->childNodes[0]->attributes[\'viewBox\']->nodeValue', $attrib->getContext()->getAccessPath());
        $this->assertSame('0 0 30 150', $attrib->getValue());

        // g1
        $g1 = $x->getRepresentation('children')->getContents()[0];
        $this->assertNotNull($g1);
        $this->assertSame('$v->childNodes[0]->childNodes[1]', $g1->getContext()->getAccessPath());

        $found_props = [];
        foreach ($g1->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertInstanceOf(DomNodeValue::class, $g1);
        $this->assertSame(4, $g1->getContext()->getDepth());
        $this->assertEquals(true, $g1->flags & AbstractValue::FLAG_GENERATED);
        $this->assertCount(1, $g1->getRepresentation('children')->getContents()); // Children with empty space removed
        $this->assertInstanceOf(DomNodeListValue::class, $found_props['childNodes']);
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame('3', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(3, $found_props['childNodes']->getChildren());
        $this->assertCount(3, $found_props['childNodes']->getRepresentation('iterator')->getContents());
        $this->assertEquals(true, $found_props['childNodes']->flags & AbstractValue::FLAG_GENERATED);

        if ($verbose) {
            $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('properties'));
            $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('methods'));
            if (KINT_PHP84) {
                $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('constants'));
            } else {
                $this->assertNull($g1->getRepresentation('constants'));
            }
            $this->assertCount(\count($found_props), $g1->getRepresentation('properties')->getContents());
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
        $g2 = $x->getRepresentation('children')->getContents()[1];
        $this->assertNotNull($g2);
        $this->assertSame('$v->childNodes[0]->childNodes[3]', $g2->getContext()->getAccessPath());

        $found_props = [];
        foreach ($g2->getChildren() as $val) {
            $found_props[$val->getContext()->getName()] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertInstanceOf(DomNodeValue::class, $g1);
        $this->assertSame(4, $g2->getContext()->getDepth());
        $this->assertEquals(true, $g2->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($g2->getRepresentation('children')); // Children with empty space removed
        $this->assertInstanceOf(DomNodeListValue::class, $found_props['childNodes']);
        $this->assertSame(KINT_PHP81, $found_props['childNodes']->getContext()->readonly);
        $this->assertSame('0', $found_props['childNodes']->getDisplaySize());
        $this->assertCount(0, $found_props['childNodes']->getChildren());
        $this->assertNull($found_props['childNodes']->getRepresentation('iterator'));
        $this->assertEquals(true, $found_props['childNodes']->flags & AbstractValue::FLAG_GENERATED);

        if ($verbose) {
            $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('properties'));
            $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('methods'));
            if (KINT_PHP84) {
                $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('constants'));
            } else {
                $this->assertNull($g2->getRepresentation('constants'));
            }
            $this->assertCount(\count($found_props), $g2->getRepresentation('properties')->getContents());
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
        $incomp = $x->getRepresentation('children')->getContents()[3]->getRepresentation('children')->getContents()[1];

        $this->assertSame('$v->childNodes[0]->childNodes[5]->childNodes[3]', $incomp->getContext()->getAccessPath());
        $this->assertSame(
            '$v->childNodes[0]->childNodes[5]->childNodes[3]->attributes[\'also-not\']->nodeValue',
            $incomp->getRepresentation('attributes')->getContents()[0]->getContext()->getAccessPath()
        );
        $this->assertSame('php-compatible', $incomp->getRepresentation('attributes')->getContents()[0]->getValue());
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
