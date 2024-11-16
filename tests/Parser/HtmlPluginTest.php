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

use Dom\DocumentType;
use Dom\HTMLElement;
use Kint\Parser\DomPlugin;
use Kint\Parser\HtmlPlugin;
use Kint\Parser\Parser;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\DomNodeValue;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\Representation\ValueRepresentation;

/**
 * @coversNothing
 */
class HtmlPluginTest extends KintTestCase
{
    public const TEST_HTML = <<<END
        <!DOCTYPE html>
        <body>
            <strong class="text">Hello world</strong>
            <DIV no:namespaces="allowed" />
        </body>
        END;

    /**
     * @covers \Kint\Parser\HtmlPlugin::parseComplete
     */
    public function testParse()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing HtmlPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new HtmlPlugin($p));

        $v = self::TEST_HTML;
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        $o = $p->parse($v, clone $b);

        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_GENERATED);
        $this->assertNull($o->getRepresentation('html'));

        $p->addPlugin(new DomPlugin($p));

        $o = $p->parse($v, clone $b);

        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_GENERATED);

        $r = $o->getRepresentation('html');

        $this->assertInstanceOf(ContainerRepresentation::class, $r);
        $this->assertCount(2, $r->getContents());
        $this->assertInstanceOf(DomNodeValue::class, $r->getContents()[0]);
        $this->assertNull($r->getContents()[0]->getDisplaySize());
        $this->assertEquals(true, $r->getContents()[0]->flags & AbstractValue::FLAG_GENERATED);
        $this->assertSame(DocumentType::class, $r->getContents()[0]->getClassName());
        $this->assertSame('!DOCTYPE html', $r->getContents()[0]->getDisplayName());
        $this->assertSame('\\Dom\\HTMLDocument::createFromString($v)->childNodes[0]', $r->getContents()[0]->getContext()->getAccessPath());

        $this->assertSame('childNodes', $r->getContents()[0]->getChildren()[0]->getContext()->getName());
        $this->assertCount(0, $r->getContents()[0]->getChildren()[0]->getChildren());

        $this->assertInstanceOf(DomNodeValue::class, $r->getContents()[1]);
        $this->assertNull($r->getContents()[1]->getDisplaySize());
        $this->assertEquals(true, $r->getContents()[1]->flags & AbstractValue::FLAG_GENERATED);
        $this->assertSame(HTMLElement::class, $r->getContents()[1]->getClassName());
        $this->assertSame('html', $r->getContents()[1]->getDisplayName());
        $this->assertSame('\\Dom\\HTMLDocument::createFromString($v)->childNodes[1]', $r->getContents()[1]->getContext()->getAccessPath());

        $this->assertSame('childNodes', $r->getContents()[1]->getChildren()[0]->getContext()->getName());
        $this->assertCount(2, $r->getContents()[1]->getChildren()[0]->getChildren());

        $b->access_path = null;
        $o = $p->parse($v, clone $b);

        $r = $o->getRepresentation('html');

        $this->assertCount(2, $r->getContents());
        $this->assertNull($r->getContents()[0]->getContext()->getAccessPath());
        $this->assertNull($r->getContents()[1]->getContext()->getAccessPath());

        $v = 'Not HTML at all lol';

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('html'));

        $v = \strtolower(self::TEST_HTML);
        $o = $p->parse($v, clone $b);
        $r = $o->getRepresentation('html');
        $this->assertNotNull($r);
        $this->assertCount(2, $r->getContents());
    }

    /**
     * @covers \Kint\Parser\HtmlPlugin::parseComplete
     */
    public function testParseDepthLimit()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing HtmlPlugin below PHP 8.4');
        }

        $p = new Parser(1);
        $p->addPlugin(new HtmlPlugin($p));
        $p->addPlugin(new DomPlugin($p));

        $v = [self::TEST_HTML];
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        $o = $p->parse($v, clone $b);
        $o = $o->getContents()[0];

        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_GENERATED);
        $r = $o->getRepresentation('html');
        $this->assertInstanceOf(ValueRepresentation::class, $r);

        $this->assertEquals(true, $r->getValue()->flags & AbstractValue::FLAG_GENERATED);
        $this->assertEquals(true, $r->getValue()->flags & AbstractValue::FLAG_DEPTH_LIMIT);
    }

    /**
     * @covers \Kint\Parser\HtmlPlugin::getTriggers
     * @covers \Kint\Parser\HtmlPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new HtmlPlugin($this->createStub(Parser::class));

        $this->assertSame(['string'], $p->getTypes());
        $this->assertSame(KINT_PHP84 ? Parser::TRIGGER_SUCCESS : Parser::TRIGGER_NONE, $p->getTriggers());
    }
}
