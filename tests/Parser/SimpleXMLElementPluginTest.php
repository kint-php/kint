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

use Kint\Parser\AbstractPlugin;
use Kint\Parser\ClassMethodsPlugin;
use Kint\Parser\Parser;
use Kint\Parser\SimpleXMLElementPlugin;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\Representation\ValueRepresentation;
use Kint\Value\SimpleXMLElementValue;
use Kint\Value\StringValue;
use ReflectionClass;
use SimpleXMLElement;
use stdClass;

/**
 * @coversNothing
 */
class SimpleXMLElementPluginTest extends KintTestCase
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

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::__construct
     * @covers \Kint\Parser\SimpleXMLElementPlugin::setParser
     */
    public function testConstruct()
    {
        $p = new Parser();
        $s = new SimpleXMLElementPlugin($p);

        $reflector = new ReflectionClass($s);
        $mprop = $reflector->getProperty('methods_plugin');
        $mprop->setAccessible(true);
        $m = $mprop->getValue($s);
        $this->assertInstanceOf(ClassMethodsPlugin::class, $m);

        $reflector = new ReflectionClass(AbstractPlugin::class);
        $mparser = $reflector->getProperty('parser');
        $mparser->setAccessible(true);

        $p = new Parser();
        $this->assertNotSame($p, $mparser->getValue($m));
        $s->setParser($p);
        $this->assertSame($p, $mparser->getValue($m));
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseBegin
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseElement
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getAttributes
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getChildren
     * @covers \Kint\Parser\SimpleXMLElementPlugin::hasChildElements
     */
    public function testParse()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = \simplexml_load_string(self::TEST_XML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        SimpleXMLElementPlugin::$verbose = true;
        $x = $p->parse($v, clone $b);
        $this->assertSame(SimpleXMLElement::class, $x->getClassName());

        // x
        $this->assertInstanceOf(SimpleXMLElementValue::class, $x);
        $this->assertSame(0, $x->getContext()->getDepth());

        $this->assertSame('$v', $x->getContext()->getName());
        $this->assertSame('$v', $x->getContext()->getAccessPath());
        $this->assertEquals(false, $x->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($x->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $x->getRepresentation('methods'));
        $this->assertSame($x->getRepresentation('children')->getContents(), $x->getDisplayChildren());
        $this->assertNull($x->getDisplayValue());
        $this->assertSame('4', $x->getDisplaySize());
        $this->assertCount(4, $x->getRepresentation('children')->getContents());

        $this->assertInstanceOf(StringValue::class, $x->getRepresentation('attributes')->getContents()[0]);
        $this->assertSame('viewBox', $x->getRepresentation('attributes')->getContents()[0]->getContext()->getName());
        $this->assertSame('(string) $v[\'viewBox\']', $x->getRepresentation('attributes')->getContents()[0]->getContext()->getAccessPath());
        $this->assertSame('0 0 30 150', $x->getRepresentation('attributes')->getContents()[0]->getValue());

        // x->g1
        $g1 = $x->getRepresentation('children')->getContents()[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $g1);
        $this->assertSame(1, $g1->getContext()->getDepth());

        $this->assertSame('g', $g1->getContext()->getName());
        $this->assertSame('$v->g', $g1->getContext()->getAccessPath());
        $this->assertEquals(true, $g1->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($g1->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('methods'));
        $this->assertSame($g1->getRepresentation('children')->getContents(), $g1->getDisplayChildren());
        $this->assertNull($g1->getDisplayValue());
        $this->assertSame('1', $g1->getDisplaySize());
        $this->assertCount(1, $g1->getRepresentation('children')->getContents());

        $this->assertInstanceOf(StringValue::class, $g1->getRepresentation('attributes')->getContents()[0]);
        $this->assertSame('stroke-width', $g1->getRepresentation('attributes')->getContents()[0]->getContext()->getName());
        $this->assertSame('(string) $v->g[\'stroke-width\']', $g1->getRepresentation('attributes')->getContents()[0]->getContext()->getAccessPath());
        $this->assertSame('2', $g1->getRepresentation('attributes')->getContents()[0]->getValue());
        $this->assertInstanceOf(StringValue::class, $g1->getRepresentation('attributes')->getContents()[1]);
        $this->assertSame('fill', $g1->getRepresentation('attributes')->getContents()[1]->getContext()->getName());
        $this->assertSame('(string) $v->g[\'fill\']', $g1->getRepresentation('attributes')->getContents()[1]->getContext()->getAccessPath());
        $this->assertSame('#FFF', $g1->getRepresentation('attributes')->getContents()[1]->getValue());

        // x->g1->inner
        $inner = $g1->getRepresentation('children')->getContents()[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $inner);
        $this->assertSame(2, $inner->getContext()->getDepth());

        $this->assertSame('inner', $inner->getContext()->getName());
        $this->assertSame('$v->g->inner', $inner->getContext()->getAccessPath());
        $this->assertEquals(true, $inner->flags & AbstractValue::FLAG_GENERATED);
        $this->assertInstanceOf(ValueRepresentation::class, $inner->getRepresentation('tostring'));
        $this->assertNull($inner->getRepresentation('children'));
        $this->assertNull($inner->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $inner->getRepresentation('methods'));
        $this->assertSame([], $inner->getDisplayChildren());
        $this->assertSame('"Text"', $inner->getDisplayValue());
        $this->assertSame('4', $inner->getDisplaySize());
        $this->assertSame('Text', $inner->getRepresentation('tostring')->getValue()->getValue());
        $this->assertSame('(string) $v->g->inner', $inner->getRepresentation('tostring')->getValue()->getContext()->getAccessPath());

        // x->g2
        $g2 = $x->getRepresentation('children')->getContents()[1];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $g2);
        $this->assertSame(1, $g2->getContext()->getDepth());

        $this->assertSame('g', $g2->getContext()->getName());
        $this->assertSame('$v->g[1]', $g2->getContext()->getAccessPath());
        $this->assertEquals(true, $g2->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($g2->getRepresentation('tostring'));
        $this->assertNull($g2->getRepresentation('children'));
        $this->assertNull($g2->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g2->getRepresentation('methods'));
        $this->assertSame([], $g2->getDisplayChildren());
        $this->assertNull($g2->getDisplayValue());
        $this->assertNull($g2->getDisplaySize());

        // x->wrap
        $wrap = $x->getRepresentation('children')->getContents()[2];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $wrap);
        $this->assertSame(1, $wrap->getContext()->getDepth());

        $this->assertSame('wrap', $wrap->getContext()->getName());
        $this->assertSame('$v->wrap', $wrap->getContext()->getAccessPath());
        $this->assertEquals(true, $wrap->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($wrap->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $wrap->getRepresentation('children'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $wrap->getRepresentation('methods'));
        $this->assertSame($wrap->getRepresentation('children')->getContents(), $wrap->getDisplayChildren());
        $this->assertNull($wrap->getDisplayValue());
        $this->assertSame('3', $wrap->getDisplaySize());
        $this->assertCount(3, $wrap->getRepresentation('children')->getContents());

        // x->wrap->wrap
        $wrap2 = $wrap->getRepresentation('children')->getContents()[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $wrap2);
        $this->assertSame(2, $wrap2->getContext()->getDepth());

        $this->assertSame('wrap', $wrap2->getContext()->getName());
        $this->assertSame('$v->wrap->wrap', $wrap2->getContext()->getAccessPath());
        $this->assertEquals(true, $wrap2->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($wrap2->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $wrap2->getRepresentation('children'));
        $this->assertNull($wrap2->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $wrap2->getRepresentation('methods'));
        $this->assertSame($wrap2->getRepresentation('children')->getContents(), $wrap2->getDisplayChildren());
        $this->assertNull($wrap2->getDisplayValue());
        $this->assertSame('1', $wrap2->getDisplaySize());
        $this->assertCount(1, $wrap2->getRepresentation('children')->getContents());

        // x->wrap->wrap->text
        $text = $wrap2->getRepresentation('children')->getContents()[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $text);
        $this->assertSame(3, $text->getContext()->getDepth());

        $this->assertSame('text', $text->getContext()->getName());
        $this->assertSame('$v->wrap->wrap->text', $text->getContext()->getAccessPath());
        $this->assertEquals(true, $text->flags & AbstractValue::FLAG_GENERATED);
        $this->assertInstanceOf(ValueRepresentation::class, $text->getRepresentation('tostring'));
        $this->assertNull($text->getRepresentation('children'));
        $this->assertNull($text->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $text->getRepresentation('methods'));
        $this->assertSame([], $text->getDisplayChildren());
        $this->assertSame('"String element"', $text->getDisplayValue());
        $this->assertSame('14', $text->getDisplaySize());
        $this->assertSame('String element', $text->getRepresentation('tostring')->getValue()->getValue());
        $this->assertSame('(string) $v->wrap->wrap->text', $text->getRepresentation('tostring')->getValue()->getContext()->getAccessPath());

        // x->wrap->no-php-compatible
        $incomp = $wrap->getRepresentation('children')->getContents()[1];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $incomp);
        $this->assertSame(2, $incomp->getContext()->getDepth());

        $this->assertSame('not-php-compatible', $incomp->getContext()->getName());
        $this->assertSame('$v->wrap->{\'not-php-compatible\'}', $incomp->getContext()->getAccessPath());
        $this->assertEquals(true, $incomp->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($incomp->getRepresentation('tostring'));
        $this->assertNull($incomp->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $incomp->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $incomp->getRepresentation('methods'));
        $this->assertSame([], $incomp->getDisplayChildren());
        $this->assertNull($incomp->getDisplayValue());
        $this->assertNull($incomp->getDisplaySize());

        $this->assertInstanceOf(StringValue::class, $incomp->getRepresentation('attributes')->getContents()[0]);
        $this->assertSame('also-not', $incomp->getRepresentation('attributes')->getContents()[0]->getContext()->getName());
        $this->assertSame('(string) $v->wrap->{\'not-php-compatible\'}[\'also-not\']', $incomp->getRepresentation('attributes')->getContents()[0]->getContext()->getAccessPath());
        $this->assertSame('php-compatible', $incomp->getRepresentation('attributes')->getContents()[0]->getValue());

        // x->wrap->value
        $value = $wrap->getRepresentation('children')->getContents()[2];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $value);
        $this->assertSame(2, $value->getContext()->getDepth());

        $this->assertSame('value', $value->getContext()->getName());
        $this->assertSame('$v->wrap->value', $value->getContext()->getAccessPath());
        $this->assertEquals(true, $value->flags & AbstractValue::FLAG_GENERATED);
        $this->assertInstanceOf(ValueRepresentation::class, $value->getRepresentation('tostring'));
        $this->assertNull($value->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $value->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $value->getRepresentation('methods'));
        $this->assertSame([], $value->getDisplayChildren());
        $this->assertSame('"Contents"', $value->getDisplayValue());
        $this->assertSame('8', $value->getDisplaySize());
        $this->assertSame('Contents', $value->getRepresentation('tostring')->getValue()->getValue());
        $this->assertSame('(string) $v->wrap->value', $value->getRepresentation('tostring')->getValue()->getContext()->getAccessPath());

        $this->assertInstanceOf(StringValue::class, $value->getRepresentation('attributes')->getContents()[0]);
        $this->assertSame('type', $value->getRepresentation('attributes')->getContents()[0]->getContext()->getName());
        $this->assertSame('(string) $v->wrap->value[\'type\']', $value->getRepresentation('attributes')->getContents()[0]->getContext()->getAccessPath());
        $this->assertSame('string', $value->getRepresentation('attributes')->getContents()[0]->getValue());

        // x->both
        $both = $x->getRepresentation('children')->getContents()[3];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $both);
        $this->assertSame(1, $both->getContext()->getDepth());

        $this->assertSame('both', $both->getContext()->getName());
        $this->assertSame('$v->both', $both->getContext()->getAccessPath());
        $this->assertEquals(true, $both->flags & AbstractValue::FLAG_GENERATED);
        $this->assertInstanceOf(ValueRepresentation::class, $both->getRepresentation('tostring'));
        $this->assertNull($both->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $both->getRepresentation('attributes'));
        $this->assertInstanceOf(ContainerRepresentation::class, $both->getRepresentation('methods'));
        $this->assertSame([], $both->getDisplayChildren());
        $this->assertSame('"And string"', $both->getDisplayValue());
        $this->assertSame('10', $both->getDisplaySize());
        $this->assertSame('And string', $both->getRepresentation('tostring')->getValue()->getValue());
        $this->assertSame('(string) $v->both', $both->getRepresentation('tostring')->getValue()->getContext()->getAccessPath());

        $this->assertInstanceOf(StringValue::class, $both->getRepresentation('attributes')->getContents()[0]);
        $this->assertSame('attribs', $both->getRepresentation('attributes')->getContents()[0]->getContext()->getName());
        $this->assertSame('(string) $v->both[\'attribs\']', $both->getRepresentation('attributes')->getContents()[0]->getContext()->getAccessPath());
        $this->assertSame('exist', $both->getRepresentation('attributes')->getContents()[0]->getValue());
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseBegin
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseElement
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getAttributes
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getChildren
     */
    public function testParseDepthLimit()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = \simplexml_load_string(self::TEST_XML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        SimpleXMLElementPlugin::$verbose = true;

        $o = $p->parse($v, clone $b);

        // All parents get depth_limit
        $wrap = $o
            ->getRepresentation('children')->getContents()[2]
            ->getRepresentation('children')->getContents()[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $wrap);
        $this->assertSame('wrap', $wrap->getContext()->getName());
        $this->assertSame(2, $wrap->getContext()->getDepth());
        $this->assertEquals(false, $wrap->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertInstanceOf(ContainerRepresentation::class, $wrap->getRepresentation('children'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertNull($wrap->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $wrap->getRepresentation('methods'));

        // Empty values with attributes don't
        $noncompat = $o
            ->getRepresentation('children')->getContents()[2]
            ->getRepresentation('children')->getContents()[1];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $noncompat);
        $this->assertSame('not-php-compatible', $noncompat->getContext()->getName());
        $this->assertSame(2, $noncompat->getContext()->getDepth());
        $this->assertEquals(false, $noncompat->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($noncompat->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $noncompat->getRepresentation('attributes'));
        $this->assertNull($noncompat->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $noncompat->getRepresentation('methods'));

        // Plain strings without attributes don't
        $inner = $o
            ->getRepresentation('children')->getContents()[0]
            ->getRepresentation('children')->getContents()[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $inner);
        $this->assertSame('inner', $inner->getContext()->getName());
        $this->assertSame(2, $inner->getContext()->getDepth());
        $this->assertEquals(false, $inner->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($inner->getRepresentation('children'));
        $this->assertNull($inner->getRepresentation('attributes'));
        $this->assertInstanceOf(ValueRepresentation::class, $inner->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $inner->getRepresentation('methods'));

        // Plain strings with attributes don't
        $value = $o
            ->getRepresentation('children')->getContents()[2]
            ->getRepresentation('children')->getContents()[2];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $value);
        $this->assertSame('value', $value->getContext()->getName());
        $this->assertSame(2, $value->getContext()->getDepth());
        $this->assertEquals(false, $value->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($value->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $value->getRepresentation('attributes'));
        $this->assertInstanceOf(ValueRepresentation::class, $value->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $value->getRepresentation('methods'));

        // Attributes (Being strings) don't
        $attr = $o
            ->getRepresentation('children')->getContents()[0]
            ->getRepresentation('attributes')->getContents()[0];
        $this->assertInstanceOf(StringValue::class, $attr);
        $this->assertSame('stroke-width', $attr->getContext()->getName());
        $this->assertSame(2, $attr->getContext()->getDepth());
        $this->assertEquals(false, $attr->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertSame($attr->getValue(), $attr->getRepresentation('contents')->getValue());

        $p->setDepthLimit(2);
        $o = $p->parse($v, clone $b);

        $wrap = $o
            ->getRepresentation('children')->getContents()[2]
            ->getRepresentation('children')->getContents()[0];
        $this->assertEquals(true, $wrap->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($wrap->getRepresentation('children'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertNull($wrap->getRepresentation('tostring'));
        $this->assertNull($wrap->getRepresentation('methods'));

        $noncompat = $o
            ->getRepresentation('children')->getContents()[2]
            ->getRepresentation('children')->getContents()[1];
        $this->assertEquals(false, $noncompat->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($noncompat->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $noncompat->getRepresentation('attributes'));
        $this->assertNull($noncompat->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $noncompat->getRepresentation('methods'));

        $inner = $o
            ->getRepresentation('children')->getContents()[0]
            ->getRepresentation('children')->getContents()[0];
        $this->assertEquals(false, $inner->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($inner->getRepresentation('children'));
        $this->assertNull($inner->getRepresentation('attributes'));
        $this->assertInstanceOf(ValueRepresentation::class, $inner->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $inner->getRepresentation('methods'));

        $value = $o
            ->getRepresentation('children')->getContents()[2]
            ->getRepresentation('children')->getContents()[2];
        $this->assertEquals(false, $value->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull($value->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $value->getRepresentation('attributes'));
        $this->assertInstanceOf(ValueRepresentation::class, $value->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $value->getRepresentation('methods'));

        $attr = $o
            ->getRepresentation('children')->getContents()[0]
            ->getRepresentation('attributes')->getContents()[0];
        $this->assertEquals(false, $attr->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertSame($attr->getValue(), $attr->getRepresentation('contents')->getValue());
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseBegin
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseElement
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getAttributes
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getChildren
     */
    public function testParseNamespaces()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = \simplexml_load_string(self::TEST_XML_NS);
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        $o = $p->parse($v, clone $b);

        // x->test:g
        $g1 = $o->getRepresentation('children')->getContents()[2];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $g1);
        $this->assertSame(1, $g1->getContext()->getDepth());

        $this->assertSame('test:g', $g1->getContext()->getName());
        $this->assertSame('$v->children(\'test\', true)->g', $g1->getContext()->getAccessPath());
        $this->assertNull($g1->getRepresentation('tostring'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $g1->getRepresentation('attributes'));
        $this->assertSame($g1->getRepresentation('children')->getContents(), $g1->getChildren());
        $this->assertSame('1', $g1->getDisplaySize());
        $this->assertCount(1, $g1->getRepresentation('children')->getContents());

        $this->assertInstanceOf(StringValue::class, $g1->getRepresentation('attributes')->getContents()[0]);
        $this->assertSame('stroke-width', $g1->getRepresentation('attributes')->getContents()[0]->getContext()->getName());
        $this->assertSame('(string) $v->children(\'test\', true)->g[\'stroke-width\']', $g1->getRepresentation('attributes')->getContents()[0]->getContext()->getAccessPath());
        $this->assertSame('2', $g1->getRepresentation('attributes')->getContents()[0]->getValue());
        $this->assertInstanceOf(StringValue::class, $g1->getRepresentation('attributes')->getContents()[1]);
        $this->assertSame('fill', $g1->getRepresentation('attributes')->getContents()[1]->getContext()->getName());
        $this->assertSame('(string) $v->children(\'test\', true)->g[\'fill\']', $g1->getRepresentation('attributes')->getContents()[1]->getContext()->getAccessPath());
        $this->assertSame('#FFF', $g1->getRepresentation('attributes')->getContents()[1]->getValue());

        // x->g
        $g2 = $o->getRepresentation('children')->getContents()[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $g2);
        $this->assertSame(1, $g2->getContext()->getDepth());

        $this->assertSame('g', $g2->getContext()->getName());
        $this->assertSame('$v->g', $g2->getContext()->getAccessPath());
        $this->assertNull($g2->getRepresentation('tostring'));
        $this->assertNull($g2->getRepresentation('children'));
        $this->assertNull($g2->getRepresentation('attributes'));
        $this->assertSame([], $g2->getChildren());
        $this->assertNull($g2->getDisplaySize());

        // x->both
        $both = $o->getRepresentation('children')->getContents()[1];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $both);
        $this->assertSame(1, $both->getContext()->getDepth());

        $this->assertSame('both', $both->getContext()->getName());
        $this->assertSame('$v->both', $both->getContext()->getAccessPath());
        $this->assertInstanceOf(ValueRepresentation::class, $both->getRepresentation('tostring'));
        $this->assertNull($both->getRepresentation('children'));
        $this->assertInstanceOf(ContainerRepresentation::class, $both->getRepresentation('attributes'));
        $this->assertSame('"And string"', $both->getDisplayValue());
        $this->assertSame('10', $both->getDisplaySize());
        $this->assertSame('And string', $both->getRepresentation('tostring')->getValue()->getValue());
        $this->assertSame('(string) $v->both', $both->getRepresentation('tostring')->getValue()->getContext()->getAccessPath());

        $this->assertInstanceOf(StringValue::class, $both->getRepresentation('attributes')->getContents()[0]);
        $this->assertSame('attribs', $both->getRepresentation('attributes')->getContents()[0]->getContext()->getName());
        $this->assertSame('(string) $v->both[\'attribs\']', $both->getRepresentation('attributes')->getContents()[0]->getContext()->getAccessPath());
        $this->assertSame('base', $both->getRepresentation('attributes')->getContents()[0]->getValue());
        $this->assertInstanceOf(StringValue::class, $both->getRepresentation('attributes')->getContents()[1]);
        $this->assertSame('test:attribs', $both->getRepresentation('attributes')->getContents()[1]->getContext()->getName());
        $this->assertSame('(string) $v->both->attributes(\'test\', true)[\'attribs\']', $both->getRepresentation('attributes')->getContents()[1]->getContext()->getAccessPath());
        $this->assertSame('exists', $both->getRepresentation('attributes')->getContents()[1]->getValue());
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseBegin
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseElement
     */
    public function testParseVerbose()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = \simplexml_load_string(self::TEST_XML);
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        SimpleXMLElementPlugin::$verbose = false;
        $o = $p->parse($v, clone $b);
        $this->assertNull($o->getRepresentation('methods'));

        SimpleXMLElementPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);
        $this->assertNotNull($o->getRepresentation('methods'));
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseBegin
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parseElement
     */
    public function testParseUnrelated()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = new stdClass();
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        SimpleXMLElementPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $this->assertNotInstanceOf(SimpleXMLElementValue::class, $o);
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getTriggers
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new SimpleXMLElementPlugin($this->createStub(Parser::class));

        $this->assertSame(['object'], $p->getTypes());
        $this->assertSame(Parser::TRIGGER_BEGIN, $p->getTriggers());
    }
}
