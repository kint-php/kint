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
use Kint\Zval\InstanceValue;
use Kint\Zval\Value;

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
     * @covers \Kint\Parser\HtmlPlugin::parse
     */
    public function testParse()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing HtmlPlugin below PHP 8.4');
        }

        $p = new Parser();
        $p->addPlugin(new HtmlPlugin($p));

        $v = self::TEST_HTML;
        $b = new Value('$v');
        $b->access_path = '$v';

        $o = $p->parse($v, clone $b);

        $this->assertArrayNotHasKey('omit_spl_id', $o->hints);
        $this->assertNull($o->getRepresentation('html'));

        $p->addPlugin(new DomPlugin($p));

        $o = $p->parse($v, clone $b);

        $this->assertArrayNotHasKey('omit_spl_id', $o->hints);

        $r = $o->getRepresentation('html');

        $this->assertNotNull($r);
        $this->assertCount(2, $r->contents);

        $this->assertInstanceOf(InstanceValue::class, $r->contents[0]);
        $this->assertArrayHasKey('omit_spl_id', $r->contents[0]->hints);
        $this->assertSame(DocumentType::class, $r->contents[0]->classname);
        $this->assertSame('!DOCTYPE html', $r->contents[0]->name);
        $this->assertSame(0, $r->contents[0]->size);
        $this->assertSame('\\Dom\\HTMLDocument::createFromString($v)->childNodes[0]', $r->contents[0]->access_path);

        $this->assertInstanceOf(InstanceValue::class, $r->contents[1]);
        $this->assertArrayHasKey('omit_spl_id', $r->contents[1]->hints);
        $this->assertSame(HTMLElement::class, $r->contents[1]->classname);
        $this->assertSame('html', $r->contents[1]->name);
        $this->assertSame(2, $r->contents[1]->size);
        $this->assertSame('\\Dom\\HTMLDocument::createFromString($v)->childNodes[1]', $r->contents[1]->access_path);

        $b->access_path = null;
        $o = $p->parse($v, clone $b);

        $r = $o->getRepresentation('html');

        $this->assertCount(2, $r->contents);
        $this->assertNull($r->contents[0]->access_path);
        $this->assertNull($r->contents[1]->access_path);

        $v = 'Not HTML at all lol';

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('html'));
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
