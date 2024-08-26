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
use Kint\Zval\BlobValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\SimpleXMLElementValue;
use Kint\Zval\Value;
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
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parse
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getAttributeRepresentation
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getChildrenRepresentation
     * @covers \Kint\Parser\SimpleXMLElementPlugin::hasChildElements
     */
    public function testParse()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = \simplexml_load_string(self::TEST_XML);
        $b = new Value('$v');
        $b->access_path = '$v';

        SimpleXMLElementPlugin::$verbose = true;
        $x = $p->parse($v, clone $b);
        $this->assertSame(SimpleXMLElement::class, $x->classname);

        // x
        $this->assertInstanceOf(SimpleXMLElementValue::class, $x);
        $this->assertSame(0, $x->depth);

        $this->assertSame('$v', $x->name);
        $this->assertSame('$v', $x->access_path);
        $this->assertArrayNotHasKey('omit_spl_id', $x->hints);
        $this->assertNull($x->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $x->getRepresentation('methods'));
        $this->assertSame($x->getRepresentation('children'), $x->value);
        $this->assertSame(4, $x->size);
        $this->assertCount(4, $x->getRepresentation('children')->contents);

        $this->assertInstanceOf(BlobValue::class, $x->getRepresentation('attributes')->contents[0]);
        $this->assertSame('viewBox', $x->getRepresentation('attributes')->contents[0]->name);
        $this->assertSame('(string) $v[\'viewBox\']', $x->getRepresentation('attributes')->contents[0]->access_path);
        $this->assertSame('0 0 30 150', $x->getRepresentation('attributes')->contents[0]->value->contents);

        // x->g1
        $g1 = $x->getRepresentation('children')->contents[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $g1);
        $this->assertSame(1, $g1->depth);

        $this->assertSame('g', $g1->name);
        $this->assertSame('$v->g', $g1->access_path);
        $this->assertArrayHasKey('omit_spl_id', $g1->hints);
        $this->assertNull($g1->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('methods'));
        $this->assertSame($g1->getRepresentation('children'), $g1->value);
        $this->assertSame(1, $g1->size);
        $this->assertCount(1, $g1->getRepresentation('children')->contents);

        $this->assertInstanceOf(BlobValue::class, $g1->getRepresentation('attributes')->contents[0]);
        $this->assertSame('stroke-width', $g1->getRepresentation('attributes')->contents[0]->name);
        $this->assertSame('(string) $v->g[\'stroke-width\']', $g1->getRepresentation('attributes')->contents[0]->access_path);
        $this->assertSame('2', $g1->getRepresentation('attributes')->contents[0]->value->contents);
        $this->assertInstanceOf(BlobValue::class, $g1->getRepresentation('attributes')->contents[1]);
        $this->assertSame('fill', $g1->getRepresentation('attributes')->contents[1]->name);
        $this->assertSame('(string) $v->g[\'fill\']', $g1->getRepresentation('attributes')->contents[1]->access_path);
        $this->assertSame('#FFF', $g1->getRepresentation('attributes')->contents[1]->value->contents);

        // x->g1->inner
        $inner = $g1->getRepresentation('children')->contents[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $inner);
        $this->assertSame(2, $inner->depth);

        $this->assertSame('inner', $inner->name);
        $this->assertSame('(string) $v->g->inner', $inner->access_path);
        $this->assertArrayHasKey('omit_spl_id', $inner->hints);
        $this->assertInstanceOf(Representation::class, $inner->getRepresentation('tostring'));
        $this->assertNull($inner->getRepresentation('children'));
        $this->assertNull($inner->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $inner->getRepresentation('methods'));
        $this->assertSame($inner->getRepresentation('tostring'), $inner->value);
        $this->assertNull($inner->size);
        $this->assertSame('4', $inner->getSize());
        $this->assertSame('Text', $inner->getRepresentation('tostring')->contents->value->contents);

        // x->g2
        $g2 = $x->getRepresentation('children')->contents[1];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $g2);
        $this->assertSame(1, $g2->depth);

        $this->assertSame('g', $g2->name);
        $this->assertSame('$v->g[1]', $g2->access_path);
        $this->assertArrayHasKey('omit_spl_id', $g2->hints);
        $this->assertNull($g2->getRepresentation('tostring'));
        $this->assertNull($g2->getRepresentation('children'));
        $this->assertNull($g2->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $g2->getRepresentation('methods'));
        $this->assertNull($g2->value);
        $this->assertNull($g2->size);

        // x->wrap
        $wrap = $x->getRepresentation('children')->contents[2];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $wrap);
        $this->assertSame(1, $wrap->depth);

        $this->assertSame('wrap', $wrap->name);
        $this->assertSame('$v->wrap', $wrap->access_path);
        $this->assertArrayHasKey('omit_spl_id', $wrap->hints);
        $this->assertNull($wrap->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('children'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('methods'));
        $this->assertSame($wrap->getRepresentation('children'), $wrap->value);
        $this->assertSame(3, $wrap->size);
        $this->assertCount(3, $wrap->getRepresentation('children')->contents);

        // x->wrap->wrap
        $wrap2 = $wrap->getRepresentation('children')->contents[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $wrap2);
        $this->assertSame(2, $wrap2->depth);

        $this->assertSame('wrap', $wrap2->name);
        $this->assertSame('$v->wrap->wrap', $wrap2->access_path);
        $this->assertArrayHasKey('omit_spl_id', $wrap2->hints);
        $this->assertNull($wrap2->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $wrap2->getRepresentation('children'));
        $this->assertNull($wrap2->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $wrap2->getRepresentation('methods'));
        $this->assertSame($wrap2->getRepresentation('children'), $wrap2->value);
        $this->assertSame(1, $wrap2->size);
        $this->assertCount(1, $wrap2->getRepresentation('children')->contents);

        // x->wrap->wrap->text
        $text = $wrap2->getRepresentation('children')->contents[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $text);
        $this->assertSame(3, $text->depth);

        $this->assertSame('text', $text->name);
        $this->assertSame('(string) $v->wrap->wrap->text', $text->access_path);
        $this->assertArrayHasKey('omit_spl_id', $text->hints);
        $this->assertInstanceOf(Representation::class, $text->getRepresentation('tostring'));
        $this->assertNull($text->getRepresentation('children'));
        $this->assertNull($text->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $text->getRepresentation('methods'));
        $this->assertSame($text->getRepresentation('tostring'), $text->value);
        $this->assertNull($text->size);
        $this->assertSame('14', $text->getSize());
        $this->assertSame('String element', $text->getRepresentation('tostring')->contents->value->contents);

        // x->wrap->no-php-compatible
        $incomp = $wrap->getRepresentation('children')->contents[1];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $incomp);
        $this->assertSame(2, $incomp->depth);

        $this->assertSame('not-php-compatible', $incomp->name);
        $this->assertSame('$v->wrap->{\'not-php-compatible\'}', $incomp->access_path);
        $this->assertArrayHasKey('omit_spl_id', $incomp->hints);
        $this->assertNull($incomp->getRepresentation('tostring'));
        $this->assertNull($incomp->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $incomp->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $incomp->getRepresentation('methods'));
        $this->assertNull($incomp->value);
        $this->assertNull($incomp->size);

        $this->assertInstanceOf(BlobValue::class, $incomp->getRepresentation('attributes')->contents[0]);
        $this->assertSame('also-not', $incomp->getRepresentation('attributes')->contents[0]->name);
        $this->assertSame('(string) $v->wrap->{\'not-php-compatible\'}[\'also-not\']', $incomp->getRepresentation('attributes')->contents[0]->access_path);
        $this->assertSame('php-compatible', $incomp->getRepresentation('attributes')->contents[0]->value->contents);

        // x->wrap->value
        $value = $wrap->getRepresentation('children')->contents[2];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $value);
        $this->assertSame(2, $value->depth);

        $this->assertSame('value', $value->name);
        $this->assertSame('(string) $v->wrap->value', $value->access_path);
        $this->assertArrayHasKey('omit_spl_id', $value->hints);
        $this->assertInstanceOf(Representation::class, $value->getRepresentation('tostring'));
        $this->assertNull($value->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $value->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $value->getRepresentation('methods'));
        $this->assertSame($value->getRepresentation('tostring'), $value->value);
        $this->assertNull($value->size);
        $this->assertSame('8', $value->getSize());
        $this->assertSame('Contents', $value->getRepresentation('tostring')->contents->value->contents);

        $this->assertInstanceOf(BlobValue::class, $value->getRepresentation('attributes')->contents[0]);
        $this->assertSame('type', $value->getRepresentation('attributes')->contents[0]->name);
        $this->assertSame('(string) $v->wrap->value[\'type\']', $value->getRepresentation('attributes')->contents[0]->access_path);
        $this->assertSame('string', $value->getRepresentation('attributes')->contents[0]->value->contents);

        // x->both
        $both = $x->getRepresentation('children')->contents[3];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $both);
        $this->assertSame(1, $both->depth);

        $this->assertSame('both', $both->name);
        $this->assertSame('(string) $v->both', $both->access_path);
        $this->assertArrayHasKey('omit_spl_id', $both->hints);
        $this->assertInstanceOf(Representation::class, $both->getRepresentation('tostring'));
        $this->assertNull($both->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $both->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $both->getRepresentation('methods'));
        $this->assertSame($both->getRepresentation('tostring'), $both->value);
        $this->assertNull($both->size);
        $this->assertSame('10', $both->getSize());
        $this->assertSame('And string', $both->getRepresentation('tostring')->contents->value->contents);

        $this->assertInstanceOf(BlobValue::class, $both->getRepresentation('attributes')->contents[0]);
        $this->assertSame('attribs', $both->getRepresentation('attributes')->contents[0]->name);
        $this->assertSame('(string) $v->both[\'attribs\']', $both->getRepresentation('attributes')->contents[0]->access_path);
        $this->assertSame('exist', $both->getRepresentation('attributes')->contents[0]->value->contents);
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parse
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getAttributeRepresentation
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getChildrenRepresentation
     */
    public function testParseDepthLimit()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = \simplexml_load_string(self::TEST_XML);
        $b = new Value('$v');
        $b->access_path = '$v';

        SimpleXMLElementPlugin::$verbose = true;

        $o = $p->parse($v, clone $b);

        // All parents get depth_limit
        $wrap = $o
            ->getRepresentation('children')->contents[2]
            ->getRepresentation('children')->contents[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $wrap);
        $this->assertSame('wrap', $wrap->name);
        $this->assertSame(2, $wrap->depth);
        $this->assertArrayNotHasKey('depth_limit', $wrap->hints);
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('children'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertNull($wrap->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $wrap->getRepresentation('methods'));

        // Empty values with attributes don't
        $noncompat = $o
            ->getRepresentation('children')->contents[2]
            ->getRepresentation('children')->contents[1];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $noncompat);
        $this->assertSame('not-php-compatible', $noncompat->name);
        $this->assertSame(2, $noncompat->depth);
        $this->assertArrayNotHasKey('depth_limit', $noncompat->hints);
        $this->assertNull($noncompat->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $noncompat->getRepresentation('attributes'));
        $this->assertNull($noncompat->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $noncompat->getRepresentation('methods'));

        // Plain strings without attributes don't
        $inner = $o
            ->getRepresentation('children')->contents[0]
            ->getRepresentation('children')->contents[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $inner);
        $this->assertSame('inner', $inner->name);
        $this->assertSame(2, $inner->depth);
        $this->assertArrayNotHasKey('depth_limit', $inner->hints);
        $this->assertNull($inner->getRepresentation('children'));
        $this->assertNull($inner->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $inner->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $inner->getRepresentation('methods'));

        // Plain strings with attributes don't
        $value = $o
            ->getRepresentation('children')->contents[2]
            ->getRepresentation('children')->contents[2];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $value);
        $this->assertSame('value', $value->name);
        $this->assertSame(2, $value->depth);
        $this->assertArrayNotHasKey('depth_limit', $value->hints);
        $this->assertNull($value->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $value->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $value->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $value->getRepresentation('methods'));

        // Attributes (Being strings) don't
        $attr = $o
            ->getRepresentation('children')->contents[0]
            ->getRepresentation('attributes')->contents[0];
        $this->assertInstanceOf(BlobValue::class, $attr);
        $this->assertSame('stroke-width', $attr->name);
        $this->assertSame(2, $attr->depth);
        $this->assertArrayNotHasKey('depth_limit', $attr->hints);
        $this->assertSame($attr->value, $attr->getRepresentation('contents'));

        $p->setDepthLimit(2);
        $o = $p->parse($v, clone $b);

        $wrap = $o
            ->getRepresentation('children')->contents[2]
            ->getRepresentation('children')->contents[0];
        $this->assertArrayHasKey('depth_limit', $wrap->hints);
        $this->assertNull($wrap->getRepresentation('children'));
        $this->assertNull($wrap->getRepresentation('attributes'));
        $this->assertNull($wrap->getRepresentation('tostring'));
        $this->assertNull($wrap->getRepresentation('methods'));

        $noncompat = $o
            ->getRepresentation('children')->contents[2]
            ->getRepresentation('children')->contents[1];
        $this->assertArrayNotHasKey('depth_limit', $noncompat->hints);
        $this->assertNull($noncompat->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $noncompat->getRepresentation('attributes'));
        $this->assertNull($noncompat->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $noncompat->getRepresentation('methods'));

        $inner = $o
            ->getRepresentation('children')->contents[0]
            ->getRepresentation('children')->contents[0];
        $this->assertArrayNotHasKey('depth_limit', $inner->hints);
        $this->assertNull($inner->getRepresentation('children'));
        $this->assertNull($inner->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $inner->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $inner->getRepresentation('methods'));

        $value = $o
            ->getRepresentation('children')->contents[2]
            ->getRepresentation('children')->contents[2];
        $this->assertArrayNotHasKey('depth_limit', $value->hints);
        $this->assertNull($value->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $value->getRepresentation('attributes'));
        $this->assertInstanceOf(Representation::class, $value->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $value->getRepresentation('methods'));

        $attr = $o
            ->getRepresentation('children')->contents[0]
            ->getRepresentation('attributes')->contents[0];
        $this->assertArrayNotHasKey('depth_limit', $attr->hints);
        $this->assertSame($attr->value, $attr->getRepresentation('contents'));
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parse
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getAttributeRepresentation
     * @covers \Kint\Parser\SimpleXMLElementPlugin::getChildrenRepresentation
     */
    public function testParseNamespaces()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = \simplexml_load_string(self::TEST_XML_NS);
        $b = new Value('$v');
        $b->access_path = '$v';

        $o = $p->parse($v, clone $b);

        // x->test:g
        $g1 = $o->getRepresentation('children')->contents[2];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $g1);
        $this->assertSame(1, $g1->depth);

        $this->assertSame('test:g', $g1->name);
        $this->assertSame('$v->children(\'test\', true)->g', $g1->access_path);
        $this->assertNull($g1->getRepresentation('tostring'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $g1->getRepresentation('attributes'));
        $this->assertSame($g1->getRepresentation('children'), $g1->value);
        $this->assertSame(1, $g1->size);
        $this->assertCount(1, $g1->getRepresentation('children')->contents);

        $this->assertInstanceOf(BlobValue::class, $g1->getRepresentation('attributes')->contents[0]);
        $this->assertSame('stroke-width', $g1->getRepresentation('attributes')->contents[0]->name);
        $this->assertSame('(string) $v->children(\'test\', true)->g[\'stroke-width\']', $g1->getRepresentation('attributes')->contents[0]->access_path);
        $this->assertSame('2', $g1->getRepresentation('attributes')->contents[0]->value->contents);
        $this->assertInstanceOf(BlobValue::class, $g1->getRepresentation('attributes')->contents[1]);
        $this->assertSame('fill', $g1->getRepresentation('attributes')->contents[1]->name);
        $this->assertSame('(string) $v->children(\'test\', true)->g[\'fill\']', $g1->getRepresentation('attributes')->contents[1]->access_path);
        $this->assertSame('#FFF', $g1->getRepresentation('attributes')->contents[1]->value->contents);

        // x->g
        $g2 = $o->getRepresentation('children')->contents[0];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $g2);
        $this->assertSame(1, $g2->depth);

        $this->assertSame('g', $g2->name);
        $this->assertSame('$v->g', $g2->access_path);
        $this->assertNull($g2->getRepresentation('tostring'));
        $this->assertNull($g2->getRepresentation('children'));
        $this->assertNull($g2->getRepresentation('attributes'));
        $this->assertNull($g2->value);
        $this->assertNull($g2->size);

        // x->both
        $both = $o->getRepresentation('children')->contents[1];
        $this->assertInstanceOf(SimpleXMLElementValue::class, $both);
        $this->assertSame(1, $both->depth);

        $this->assertSame('both', $both->name);
        $this->assertSame('(string) $v->both', $both->access_path);
        $this->assertInstanceOf(Representation::class, $both->getRepresentation('tostring'));
        $this->assertNull($both->getRepresentation('children'));
        $this->assertInstanceOf(Representation::class, $both->getRepresentation('attributes'));
        $this->assertSame($both->getRepresentation('tostring'), $both->value);
        $this->assertNull($both->size);
        $this->assertSame('10', $both->getSize());
        $this->assertSame('And string', $both->getRepresentation('tostring')->contents->value->contents);

        $this->assertInstanceOf(BlobValue::class, $both->getRepresentation('attributes')->contents[0]);
        $this->assertSame('attribs', $both->getRepresentation('attributes')->contents[0]->name);
        $this->assertSame('(string) $v->both[\'attribs\']', $both->getRepresentation('attributes')->contents[0]->access_path);
        $this->assertSame('base', $both->getRepresentation('attributes')->contents[0]->value->contents);
        $this->assertInstanceOf(BlobValue::class, $both->getRepresentation('attributes')->contents[1]);
        $this->assertSame('test:attribs', $both->getRepresentation('attributes')->contents[1]->name);
        $this->assertSame('(string) $v->both->attributes(\'test\', true)[\'attribs\']', $both->getRepresentation('attributes')->contents[1]->access_path);
        $this->assertSame('exists', $both->getRepresentation('attributes')->contents[1]->value->contents);
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parse
     */
    public function testParseVerbose()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = \simplexml_load_string(self::TEST_XML);
        $b = new Value('$v');
        $b->access_path = '$v';

        SimpleXMLElementPlugin::$verbose = false;
        $o = $p->parse($v, clone $b);
        $this->assertNull($o->getRepresentation('methods'));

        SimpleXMLElementPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);
        $this->assertNotNull($o->getRepresentation('methods'));
    }

    /**
     * @covers \Kint\Parser\SimpleXMLElementPlugin::parse
     */
    public function testParseUnrelated()
    {
        $p = new Parser();
        $p->addPlugin(new SimpleXMLElementPlugin($p));

        $v = new stdClass();
        $b = new Value('$v');
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
