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
use DOMElement;
use Kint\Parser\Parser;
use Kint\Parser\XmlPlugin;
use Kint\Test\KintTestCase;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\Context\ClassOwnedContext;
use Kint\Zval\InstanceValue;
use SimpleXMLElement;

/**
 * @coversNothing
 */
class XmlPluginTest extends KintTestCase
{
    public const TEST_XML = <<<END
        <?xml version="1.0" encoding="UTF-8"?>
        <x viewBox="0 0 30 150">
            <g stroke-width="2" fill="#FFF">
            </g>
        </x>
        END;

    public const TEST_XML_INVALID = '<?xml>';

    /**
     * @covers \Kint\Parser\XmlPlugin::parseComplete
     * @covers \Kint\Parser\XmlPlugin::xmlToSimpleXML
     */
    public function testParseSimpleXML()
    {
        $p = new Parser();
        $p->addPlugin(new XmlPlugin($p));

        $v = self::TEST_XML;
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        XmlPlugin::$parse_method = 'SimpleXML';

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->hasHint('omit_spl_id'));

        $r = $o->getRepresentation('xml');

        $this->assertNotNull($r);
        $this->assertInstanceOf(InstanceValue::class, $r->contents);
        $this->assertTrue($r->contents->hasHint('omit_spl_id'));
        $this->assertSame(SimpleXMLElement::class, $r->contents->getClassName());
        $this->assertSame('2', $r->contents->getDisplaySize());
        $this->assertInstanceOf(BaseContext::class, $r->contents->getContext());
        $this->assertNotInstanceOf(ClassOwnedContext::class, $r->contents->getContext());
        $this->assertSame('x', $r->contents->getContext()->getName());
        $this->assertSame('simplexml_load_string($v)', $r->contents->getContext()->getAccessPath());

        $b->access_path = null;
        $o = $p->parse($v, clone $b);

        $r = $o->getRepresentation('xml');

        $this->assertInstanceOf(InstanceValue::class, $r->contents);
        $this->assertNull($r->contents->getContext()->getAccessPath());

        $v = self::TEST_XML_INVALID;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('xml'));
    }

    /**
     * @covers \Kint\Parser\XmlPlugin::parseComplete
     * @covers \Kint\Parser\XmlPlugin::xmlToDOMDocument
     */
    public function testParseDOMDocument()
    {
        $p = new Parser();
        $p->addPlugin(new XmlPlugin($p));

        $v = self::TEST_XML;
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        XmlPlugin::$parse_method = 'DOMDocument';

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->hasHint('omit_spl_id'));

        $r = $o->getRepresentation('xml');

        $this->assertNotNull($r);
        $this->assertInstanceOf(InstanceValue::class, $r->contents);
        $this->assertTrue($r->contents->hasHint('omit_spl_id'));
        $this->assertSame(DOMElement::class, $r->contents->getClassName());
        if (KINT_PHP81) {
            $this->assertGreaterThan(0, (int) $r->contents->getDisplaySize());
        } else {
            $this->assertSame(0, (int) $r->contents->getDisplaySize());
        }
        $this->assertInstanceOf(BaseContext::class, $r->contents->getContext());
        $this->assertNotInstanceOf(ClassOwnedContext::class, $r->contents->getContext());
        $this->assertSame('x', $r->contents->getContext()->getName());
        $this->assertSame('(function($s){$x = new \\DomDocument(); $x->loadXML($s); return $x;})($v)->firstChild', $r->contents->getContext()->getAccessPath());

        $b->access_path = null;
        $o = $p->parse($v, clone $b);

        $r = $o->getRepresentation('xml');

        $this->assertInstanceOf(InstanceValue::class, $r->contents);
        $this->assertNull($r->contents->getContext()->getAccessPath());

        $v = self::TEST_XML_INVALID;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('xml'));
    }

    /**
     * @covers \Kint\Parser\XmlPlugin::parseComplete
     * @covers \Kint\Parser\XmlPlugin::xmlToXMLDocument
     */
    public function testParseXMLDocument()
    {
        $p = new Parser();
        $p->addPlugin(new XmlPlugin($p));

        $v = self::TEST_XML;
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        XmlPlugin::$parse_method = 'XMLDocument';

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->hasHint('omit_spl_id'));

        $r = $o->getRepresentation('xml');

        if (!KINT_PHP84) {
            $this->assertNull($r);

            return;
        }

        $this->assertNotNull($r);
        $this->assertInstanceOf(InstanceValue::class, $r->contents);
        $this->assertTrue($r->contents->hasHint('omit_spl_id'));
        $this->assertSame(Element::class, $r->contents->getClassName());
        $this->assertGreaterThan(0, (int) $r->contents->getDisplaySize());
        $this->assertInstanceOf(BaseContext::class, $r->contents->getContext());
        $this->assertNotInstanceOf(ClassOwnedContext::class, $r->contents->getContext());
        $this->assertSame('x', $r->contents->getContext()->getName());
        $this->assertSame('\\Dom\\XMLDocument::createFromString($v)->firstChild', $r->contents->getContext()->getAccessPath());

        $b->access_path = null;
        $o = $p->parse($v, clone $b);

        $r = $o->getRepresentation('xml');

        $this->assertInstanceOf(InstanceValue::class, $r->contents);
        $this->assertNull($r->contents->getContext()->getAccessPath());

        $v = self::TEST_XML_INVALID;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('xml'));
    }

    /**
     * @covers \Kint\Parser\XmlPlugin::parseComplete
     */
    public function testBadParseMethod()
    {
        $p = new Parser();

        $v = self::TEST_XML;
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        $o = $p->parse($v, clone $b);

        $p->addPlugin(new XmlPlugin($p));

        $o2 = $p->parse($v, clone $b);

        XmlPlugin::$parse_method = 'nonexistant';

        $o3 = $p->parse($v, clone $b);

        $this->assertNotEquals($o, $o2);
        $this->assertEquals($o, $o3);
    }

    /**
     * @covers \Kint\Parser\XmlPlugin::getTriggers
     * @covers \Kint\Parser\XmlPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new XmlPlugin($this->createStub(Parser::class));

        $this->assertSame(['string'], $p->getTypes());
        $this->assertSame(Parser::TRIGGER_SUCCESS, $p->getTriggers());
    }
}
