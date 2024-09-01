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

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Text;
use Dom\XMLDocument;
use Kint\Parser\AbstractPlugin;
use Kint\Parser\ClassMethodsPlugin;
use Kint\Parser\ClassStaticsPlugin;
use Kint\Parser\DomPlugin;
use Kint\Parser\IteratorPlugin;
use Kint\Parser\Parser;
use Kint\Test\KintTestCase;
use Kint\Zval\BlobValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
use ReflectionClass;

/**
 * @coversNothing
 */
class DomPluginTest extends KintTestCase
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
            <DIV no:namespaces="allowed" />
        </body>
        END;

    /**
     * @covers \Kint\Parser\DomPlugin::__construct
     * @covers \Kint\Parser\DomPlugin::setParser
     */
    public function testConstruct()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $d = new DomPlugin($p);

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

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testParseXML()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = XMLDocument::createFromString(self::TEST_XML);
        $b = new Value('$v');
        $b->access_path = '$v';

        DomPlugin::$verbose = false;
        $o = $p->parse($v, clone $b);

        $this->basicAssertionsXml($o, false);
    }

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testParseXMLVerbose()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = XMLDocument::createFromString(self::TEST_XML);
        $b = new Value('$v');
        $b->access_path = '$v';
        DomPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $this->basicAssertionsXml($o, true);

        $found_props = [];
        foreach ($o->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertCount(1, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertArrayHasKey('blacklist', $found_props['firstChild']->hints);
        $this->assertSame(Element::class, $found_props['firstChild']->classname);
        $this->assertArrayHasKey('blacklist', $found_props['lastChild']->hints);
        $this->assertSame(Element::class, $found_props['lastChild']->classname);

        $root_node = \reset($found_props['childNodes']->getRepresentation('iterator')->contents);

        $root_props = [];
        foreach ($root_node->value->contents as $val) {
            $root_props[$val->name] = $val;
        }

        $this->assertArrayHasKey('blacklist', $root_props['ownerDocument']->hints);
        $this->assertSame(XMLDocument::class, $root_props['ownerDocument']->classname);
        $this->assertArrayHasKey('blacklist', $root_props['parentNode']->hints);
        $this->assertSame(XMLDocument::class, $root_props['parentNode']->classname);
        $this->assertArrayHasKey('blacklist', $root_props['firstElementChild']->hints);
        $this->assertSame(Element::class, $root_props['firstElementChild']->classname);
        $this->assertArrayHasKey('blacklist', $root_props['lastElementChild']->hints);
        $this->assertSame(Element::class, $root_props['lastElementChild']->classname);
        $this->assertArrayHasKey('blacklist', $root_props['firstChild']->hints);
        $this->assertSame(Text::class, $root_props['firstChild']->classname);
        $this->assertArrayHasKey('blacklist', $root_props['lastChild']->hints);
        $this->assertSame(Text::class, $root_props['lastChild']->classname);
    }

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testNamespaces()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = XMLDocument::createFromString(self::TEST_XML_NS);
        $b = new Value('$v');
        $b->access_path = '$v';

        DomPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $root = $o->getRepresentation('children')->contents[0];
        $this->assertSame('$v->childNodes[0]', $root->access_path);

        $root_props = [];
        foreach ($root->value->contents as $val) {
            $root_props[$val->name] = $val;
        }

        $this->assertSame('x', $root_props['tagName']->value->contents);
        $this->assertSame('x', $root_props['localName']->value->contents);
        $this->assertSame('http://localhost/', $root_props['namespaceURI']->value->contents);
        $this->assertSame('$v->childNodes[0]->namespaceURI', $root_props['namespaceURI']->access_path);
        $this->assertSame('$v->childNodes[0]->attributes', $root_props['attributes']->access_path);
        $this->assertSame(3, $root_props['attributes']->size);

        $this->assertEquals(
            $root_props['attributes']->getRepresentation('iterator')->contents,
            $root->getRepresentation('attributes')->contents
        );

        $attribs = [];
        foreach ($root->getRepresentation('attributes')->contents as $val) {
            $attribs[$val->name] = $val;
        }

        $this->assertCount(3, $attribs);

        $this->assertSame('http://localhost/', $attribs['xmlns']->value->contents);
        $this->assertSame('$v->childNodes[0]->attributes[\'xmlns\']->nodeValue', $attribs['xmlns']->access_path);

        $this->assertSame('http://localhost/test', $attribs['xmlns:test']->value->contents);
        $this->assertSame('$v->childNodes[0]->attributes[\'xmlns:test\']->nodeValue', $attribs['xmlns:test']->access_path);

        $this->assertSame('0 0 30 150', $attribs['viewBox']->value->contents);
        $this->assertSame('$v->childNodes[0]->attributes[\'viewBox\']->nodeValue', $attribs['viewBox']->access_path);

        $g = $root->getRepresentation('children')->contents[0];
        $this->assertSame('test:g', $g->name);
        $this->assertSame('$v->childNodes[0]->childNodes[1]', $g->access_path);

        $gprops = [];
        foreach ($g->value->contents as $val) {
            $gprops[$val->name] = $val;
        }

        $this->assertSame('test:g', $gprops['tagName']->value->contents);
        $this->assertSame('g', $gprops['localName']->value->contents);
        $this->assertSame('http://localhost/test', $gprops['namespaceURI']->value->contents);
        $this->assertSame('$v->childNodes[0]->childNodes[1]->namespaceURI', $gprops['namespaceURI']->access_path);
        $this->assertSame('$v->childNodes[0]->childNodes[1]->attributes', $gprops['attributes']->access_path);
        $this->assertSame(2, $gprops['attributes']->size);

        $g = $root->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g->name);
        $this->assertSame('$v->childNodes[0]->childNodes[3]', $g->access_path);

        $gprops = [];
        foreach ($g->value->contents as $val) {
            $gprops[$val->name] = $val;
        }

        $this->assertSame('g', $gprops['tagName']->value->contents);
        $this->assertSame('g', $gprops['localName']->value->contents);
        $this->assertSame('http://localhost/test', $gprops['namespaceURI']->value->contents);
        $this->assertSame('$v->childNodes[0]->childNodes[3]->namespaceURI', $gprops['namespaceURI']->access_path);
        $this->assertSame('$v->childNodes[0]->childNodes[3]->attributes', $gprops['attributes']->access_path);
        $this->assertSame(1, $gprops['attributes']->size);

        $both = $root->getRepresentation('children')->contents[3];
        $this->assertSame('both', $both->name);
        $this->assertSame('$v->childNodes[0]->childNodes[5]', $both->access_path);

        $attribs = [];
        foreach ($both->getRepresentation('attributes')->contents as $val) {
            $attribs[$val->name] = $val;
        }

        $this->assertCount(2, $attribs);

        $this->assertSame('base', $attribs['attribs']->value->contents);
        $this->assertSame('$v->childNodes[0]->childNodes[5]->attributes[\'attribs\']->nodeValue', $attribs['attribs']->access_path);

        $this->assertSame('exists', $attribs['test:attribs']->value->contents);
        $this->assertSame('$v->childNodes[0]->childNodes[5]->attributes[\'test:attribs\']->nodeValue', $attribs['test:attribs']->access_path);
    }

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testDepthLimit()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = XMLDocument::createFromString(self::TEST_XML);
        $b = new Value('$v');
        $b->access_path = '$v';
        DomPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $o_props = [];
        foreach ($o->value->contents as $val) {
            $o_props[$val->name] = $val;
        }

        $x = $o->getRepresentation('children')->contents[0];
        $this->assertSame('x', $x->name);
        $this->assertSame(5, $x->size);
        $this->assertArrayNotHasKey('depth_limit', $x->hints);
        $this->assertSame($x->value, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('statics'));

        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertSame('g', $g1->name);
        $this->assertSame(1, $g1->size);
        $this->assertArrayNotHasKey('depth_limit', $g1->hints);
        $this->assertSame($g1->value, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('statics'));

        $found_props = [];
        foreach ($g1->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayNotHasKey('depth_limit', $found_props['childNodes']->hints);

        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g2->name);
        $this->assertSame(0, $g2->size);
        $this->assertArrayNotHasKey('depth_limit', $g2->hints);
        $this->assertSame($g2->value, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('statics'));

        $text = $x->getRepresentation('children')->contents[2];
        $this->assertSame('#text', $text->name);
        $this->assertSame(22, $text->size);
        $this->assertArrayNotHasKey('depth_limit', $text->hints);
        $this->assertNotNull($text->value);
        $this->assertNull($text->getRepresentation('properties'));
        $this->assertNull($text->getRepresentation('methods'));
        $this->assertNull($text->getRepresentation('statics'));

        $wrap = $x->getRepresentation('children')->contents[3];
        $this->assertSame('wrap', $wrap->name);
        $this->assertSame(3, $wrap->size);
        $this->assertArrayNotHasKey('depth_limit', $wrap->hints);
        $this->assertNotNull($wrap->value);
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('statics'));

        $p->setDepthLimit(4);
        $o = $p->parse($v, clone $b);

        $x = $o->getRepresentation('children')->contents[0];
        $this->assertSame('x', $x->name);
        $this->assertSame(5, $x->size);
        $this->assertArrayNotHasKey('depth_limit', $x->hints);
        $this->assertSame($x->value, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('statics'));

        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertSame('g', $g1->name);
        $this->assertNull($g1->size);
        $this->assertArrayHasKey('depth_limit', $g1->hints);
        $this->assertSame($g1->value, $g1->getRepresentation('properties'));
        $this->assertNull($g1->getRepresentation('properties'));
        $this->assertNull($g1->getRepresentation('attributes'));
        $this->assertNull($g1->getRepresentation('statics'));

        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g2->name);
        $this->assertNull($g2->size);
        $this->assertArrayHasKey('depth_limit', $g2->hints);
        $this->assertSame($g2->value, $g2->getRepresentation('properties'));
        $this->assertNull($g2->getRepresentation('properties'));
        $this->assertNull($g2->getRepresentation('attributes'));
        $this->assertNull($g2->getRepresentation('statics'));

        $text = $x->getRepresentation('children')->contents[2];
        $this->assertSame('#text', $text->name);
        $this->assertSame(22, $text->size);
        $this->assertArrayNotHasKey('depth_limit', $text->hints);
        $this->assertNotNull($text->value);
        $this->assertNull($text->getRepresentation('properties'));
        $this->assertNull($text->getRepresentation('attributes'));
        $this->assertNull($text->getRepresentation('statics'));

        $wrap = $x->getRepresentation('children')->contents[3];
        $this->assertSame('wrap', $wrap->name);
        $this->assertNull($wrap->size);
        $this->assertArrayHasKey('depth_limit', $wrap->hints);
        $this->assertSame($wrap->value, $wrap->getRepresentation('properties'));
        $this->assertNull($wrap->getRepresentation('properties'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertNull($wrap->getRepresentation('statics'));

        $p->setDepthLimit(5);
        $o = $p->parse($v, clone $b);

        $x = $o->getRepresentation('children')->contents[0];
        $this->assertSame('x', $x->name);
        $this->assertSame(5, $x->size);
        $this->assertArrayNotHasKey('depth_limit', $x->hints);
        $this->assertSame($x->value, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('statics'));

        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertSame('g', $g1->name);
        $this->assertNull($g1->size);
        $this->assertArrayNotHasKey('depth_limit', $g1->hints);
        $this->assertSame($g1->value, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('statics'));

        $found_props = [];
        foreach ($g1->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayNotHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('depth_limit', $found_props['childNodes']->hints);
        $this->assertArrayNotHasKey('depth_limit', $found_props['attributes']->hints);

        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertSame('g', $g2->name);
        $this->assertSame(0, $g2->size);
        $this->assertArrayNotHasKey('depth_limit', $g2->hints);
        $this->assertSame($g2->value, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('statics'));
    }

    /**
     * @covers \Kint\Parser\DomPlugin::parse
     * @covers \Kint\Parser\DomPlugin::parseText
     * @covers \Kint\Parser\DomPlugin::parseList
     * @covers \Kint\Parser\DomPlugin::parseNode
     * @covers \Kint\Parser\DomPlugin::parseProperty
     * @covers \Kint\Parser\DomPlugin::getChildrenRepresentation
     */
    public function testHTML()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing DomPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new ClassMethodsPlugin($p));
        $p->addPlugin(new ClassStaticsPlugin($p));
        $p->addPlugin(new DomPlugin($p));
        $p->addPlugin(new IteratorPlugin($p)); // Just to make sure

        $v = HTMLDocument::createFromString(self::TEST_HTML);
        $b = new Value('$v');
        $b->access_path = '$v';
        DomPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $expected_props = [
            'NODE_PROPS' => \count(DomPlugin::NODE_PROPS),
            'ELEMENT_PROPS' => \count(DomPlugin::ELEMENT_PROPS + DomPlugin::NODE_PROPS),
        ];

        $found_props = [];
        foreach ($o->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayNotHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(0, $o->depth);
        $this->assertSame(2, $o->size); // Node size should be the same as...
        $this->assertCount(2, $o->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertTrue($found_props['childNodes']->readonly);
        $this->assertSame(2, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(2, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertNull($o->getRepresentation('attributes'));

        $this->assertInstanceOf(Representation::class, $o->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $o->getRepresentation('methods'));
        $this->assertCount(\count($found_props), $o->getRepresentation('properties')->contents);
        $this->assertCount($expected_props['NODE_PROPS'], $found_props);
        $this->assertTrue($found_props['nodeType']->readonly);
        $this->assertTrue($found_props['textContent']->readonly);

        // html
        $html = $found_props['childNodes']->getRepresentation('iterator')->contents[1] ?? null;

        $this->assertNotNull($html);
        $this->assertSame('$v->childNodes[1]', $html->access_path);
        $this->assertSame('$v->childNodes[1]', $o->getRepresentation('children')->contents[1]->access_path);

        $found_props = [];
        foreach ($html->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(2, $html->depth);
        $this->assertSame(2, $html->size); // Node size should be the same as...
        $this->assertCount(2, $html->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertTrue($found_props['childNodes']->readonly);
        $this->assertSame(2, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(2, $found_props['childNodes']->getRepresentation('iterator')->contents); // Actual elements of childNodes
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);

        $this->assertInstanceOf(Representation::class, $html->getRepresentation('properties'));
        $this->assertInstanceOf(Representation::class, $html->getRepresentation('methods'));
        $this->assertCount(\count($found_props), $html->getRepresentation('properties')->contents);
        $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
        $this->assertTrue($found_props['nodeType']->readonly);
        $this->assertFalse($found_props['textContent']->readonly);

        // head
        $head = $html->getRepresentation('children')->contents[0];
        $this->assertSame('head', $head->name);
        $this->assertSame('$v->childNodes[1]->childNodes[0]', $head->access_path);

        // body
        $body = $html->getRepresentation('children')->contents[1];
        $this->assertNotNull($body);
        $this->assertSame('$v->childNodes[1]->childNodes[1]', $body->access_path);

        $found_props = [];
        foreach ($body->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('BODY', $found_props['nodeName']->value->contents);
        $this->assertSame('body', $found_props['localName']->value->contents);
        $this->assertSame('BODY', $found_props['tagName']->value->contents);

        // strong
        $strong = $body->getRepresentation('children')->contents[0];
        $this->assertNotNull($strong);
        $this->assertSame('$v->childNodes[1]->childNodes[1]->childNodes[1]', $strong->access_path);

        $found_props = [];
        foreach ($strong->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('STRONG', $found_props['nodeName']->value->contents);
        $this->assertSame('strong', $found_props['localName']->value->contents);
        $this->assertSame('STRONG', $found_props['tagName']->value->contents);

        $attributes = $strong->getRepresentation('attributes');
        $this->assertInstanceOf(Representation::class, $attributes);

        $attrib = $attributes->contents[0];
        $this->assertInstanceOf(BlobValue::class, $attrib);
        $this->assertSame('class', $attrib->name);
        $this->assertSame('$v->childNodes[1]->childNodes[1]->childNodes[1]->attributes[\'class\']->nodeValue', $attrib->access_path);
        $this->assertSame('text', $attrib->value->contents);

        $this->assertSame('text', $found_props['className']->value->contents);
        $this->assertSame('text', $found_props['classList']->getRepresentation('iterator')->contents[0]->value->contents);

        // div
        $div = $body->getRepresentation('children')->contents[1];
        $this->assertNotNull($div);
        $this->assertSame('$v->childNodes[1]->childNodes[1]->childNodes[3]', $div->access_path);

        $found_props = [];
        foreach ($div->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame('DIV', $found_props['nodeName']->value->contents);
        $this->assertSame('div', $found_props['localName']->value->contents);
        $this->assertSame('DIV', $found_props['tagName']->value->contents);

        $attributes = $div->getRepresentation('attributes');
        $this->assertInstanceOf(Representation::class, $attributes);

        $attrib = $attributes->contents[0];
        $this->assertInstanceOf(BlobValue::class, $attrib);
        $this->assertSame('no:namespaces', $attrib->name);
        $this->assertSame('$v->childNodes[1]->childNodes[1]->childNodes[3]->attributes[\'no:namespaces\']->nodeValue', $attrib->access_path);
        $this->assertSame('allowed', $attrib->value->contents);
    }

    /**
     * @covers \Kint\Parser\DomPlugin::getTriggers
     * @covers \Kint\Parser\DomPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new DomPlugin($this->createStub(Parser::class));

        $this->assertSame(['object'], $p->getTypes());
        $this->assertSame(KINT_PHP84 ? Parser::TRIGGER_BEGIN : Parser::TRIGGER_NONE, $p->getTriggers());
    }

    protected function basicAssertionsXml(Value $o, bool $verbose)
    {
        $expected_props = [
            'NODE_PROPS' => \count(DomPlugin::NODE_PROPS),
            'ELEMENT_PROPS' => \count(DomPlugin::ELEMENT_PROPS + DomPlugin::NODE_PROPS),
        ];

        $found_props = [];
        foreach ($o->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayNotHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(0, $o->depth);
        $this->assertArrayNotHasKey('omit_spl_id', $o->hints);
        $this->assertSame(1, $o->size); // Node size should be the same as...
        $this->assertCount(1, $o->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertTrue($found_props['childNodes']->readonly);
        $this->assertSame(1, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(1, $found_props['childNodes']->getRepresentation('iterator')->contents);
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('omit_spl_id', $found_props['childNodes']->hints);
        $this->assertNull($o->getRepresentation('attributes'));

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('methods'));
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('statics'));
            $this->assertCount(\count($found_props), $o->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['NODE_PROPS'], $found_props);
            $this->assertTrue($found_props['nodeType']->readonly);
            $this->assertTrue($found_props['textContent']->readonly);
        } else {
            $this->assertNull($o->getRepresentation('properties'));
            $this->assertNull($o->getRepresentation('methods'));
            $this->assertNull($o->getRepresentation('statics'));
            $this->assertCount(2, $found_props);
        }

        // x
        $x = $found_props['childNodes']->getRepresentation('iterator')->contents[0] ?? null;

        $this->assertNotNull($x);
        $this->assertSame('$v->childNodes[0]', $x->access_path);
        $this->assertSame('$v->childNodes[0]', $o->getRepresentation('children')->contents[0]->access_path);

        $found_props = [];
        foreach ($x->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(2, $x->depth);
        $this->assertArrayHasKey('omit_spl_id', $x->hints);
        $this->assertSame(5, $x->size); // Node size should be the same as...
        $this->assertCount(5, $x->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertTrue($found_props['childNodes']->readonly);
        $this->assertSame(9, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(9, $found_props['childNodes']->getRepresentation('iterator')->contents); // Actual elements of childNodes
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('omit_spl_id', $found_props['childNodes']->hints);

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $x->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
            $this->assertInstanceOf(Representation::class, $o->getRepresentation('statics'));
            $this->assertCount(\count($found_props), $x->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
            $this->assertTrue($found_props['nodeType']->readonly);
            $this->assertFalse($found_props['textContent']->readonly);
        } else {
            $this->assertNull($x->getRepresentation('properties'));
            $this->assertNull($x->getRepresentation('methods'));
            $this->assertNull($x->getRepresentation('statics'));
            $this->assertCount(3, $found_props);
        }

        // x attributes
        $attributes = $x->getRepresentation('attributes');
        $this->assertInstanceOf(Representation::class, $attributes);

        $attrib = $attributes->contents[0];
        $this->assertInstanceOf(BlobValue::class, $attrib);
        $this->assertSame('viewBox', $attrib->name);
        $this->assertSame('$v->childNodes[0]->attributes[\'viewBox\']->nodeValue', $attrib->access_path);
        $this->assertSame('0 0 30 150', $attrib->value->contents);

        // g1
        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertNotNull($g1);
        $this->assertSame('$v->childNodes[0]->childNodes[1]', $g1->access_path);

        $found_props = [];
        foreach ($g1->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(4, $g1->depth);
        $this->assertArrayHasKey('omit_spl_id', $g1->hints);
        $this->assertSame(1, $g1->size); // Node size should be the same as...
        $this->assertCount(1, $g1->getRepresentation('children')->contents); // Children with empty space removed
        $this->assertTrue($found_props['childNodes']->readonly);
        $this->assertSame(3, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(3, $found_props['childNodes']->getRepresentation('iterator')->contents); // Actual elements of childNodes
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('omit_spl_id', $found_props['childNodes']->hints);

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
            $this->assertInstanceOf(Representation::class, $g1->getRepresentation('statics'));
            $this->assertCount(\count($found_props), $g1->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
            $this->assertTrue($found_props['nodeType']->readonly);
            $this->assertFalse($found_props['textContent']->readonly);
        } else {
            $this->assertNull($g1->getRepresentation('properties'));
            $this->assertNull($g1->getRepresentation('methods'));
            $this->assertNull($g1->getRepresentation('statics'));
            $this->assertCount(3, $found_props);
        }

        // g2
        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertNotNull($g2);
        $this->assertSame('$v->childNodes[0]->childNodes[3]', $g2->access_path);

        $found_props = [];
        foreach ($g2->value->contents as $val) {
            $found_props[$val->name] = $val;
        }

        $this->assertArrayHasKey('attributes', $found_props);
        $this->assertArrayHasKey('childNodes', $found_props);
        $this->assertArrayHasKey('nodeValue', $found_props);

        $this->assertSame(4, $g2->depth);
        $this->assertArrayHasKey('omit_spl_id', $g2->hints);
        $this->assertSame(0, $g2->size); // Node size should be the same as...
        $this->assertNull($g2->getRepresentation('children')); // Children with empty space removed
        $this->assertTrue($found_props['childNodes']->readonly);
        $this->assertSame(0, $found_props['childNodes']->size); // Actual elements of childNodes
        $this->assertCount(0, $found_props['childNodes']->getRepresentation('iterator')->contents); // Actual elements of childNodes
        $this->assertArrayHasKey('iterator_primary', $found_props['childNodes']->hints);
        $this->assertArrayHasKey('omit_spl_id', $found_props['childNodes']->hints);

        if ($verbose) {
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('properties'));
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
            $this->assertInstanceOf(Representation::class, $g2->getRepresentation('statics'));
            $this->assertCount(\count($found_props), $g2->getRepresentation('properties')->contents);
            $this->assertCount($expected_props['ELEMENT_PROPS'], $found_props);
            $this->assertTrue($found_props['nodeType']->readonly);
            $this->assertFalse($found_props['textContent']->readonly);
        } else {
            $this->assertNull($g2->getRepresentation('properties'));
            $this->assertNull($g2->getRepresentation('methods'));
            $this->assertNull($g2->getRepresentation('statics'));
            $this->assertCount(3, $found_props);
        }

        // not-php-compatible
        $incomp = $x->getRepresentation('children')->contents[3]->getRepresentation('children')->contents[1];

        $this->assertSame('$v->childNodes[0]->childNodes[5]->childNodes[3]', $incomp->access_path);
        $this->assertSame(
            '$v->childNodes[0]->childNodes[5]->childNodes[3]->attributes[\'also-not\']->nodeValue',
            $incomp->getRepresentation('attributes')->contents[0]->access_path
        );
        $this->assertSame('php-compatible', $incomp->getRepresentation('attributes')->contents[0]->value->contents);
    }
}
