<?php

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

use Kint\Parser\ArrayLimitPlugin;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Test\KintTestCase;
use Kint\Zval\Value;
use Prophecy\Argument;

class ArrayLimitPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::getTypes
     */
    public function testGetTypes()
    {
        $a = new ArrayLimitPlugin();

        $this->assertSame(['array'], $a->getTypes());
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::getTriggers
     */
    public function testGetTriggers()
    {
        $a = new ArrayLimitPlugin();

        $this->assertSame(Parser::TRIGGER_BEGIN, $a->getTriggers());
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parse
     */
    public function testParse()
    {
        $p = new Parser();
        $alp = new ArrayLimitPlugin();
        $b = Value::blank('$v', '$v');
        $v = \range(1, 99);

        $p->addPlugin($alp);

        $completed = false;
        $pp = new ProxyPlugin(
            ['array'],
            Parser::TRIGGER_COMPLETE,
            function (&$var) use (&$completed) {
                if (\count($var) > 60) {
                    $completed = true;
                }
            }
        );

        $p->addPlugin($pp);

        $o = $p->parse($v, clone $b);

        $this->assertCount(\count($v), $o->value->contents);
        $this->assertTrue($completed);

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $completed = false;
        $o = $p->parse($v, clone $b);

        $this->assertCount(ArrayLimitPlugin::$limit + 1, $o->value->contents);
        $this->assertFalse($completed);

        $elide = \end($o->value->contents);
        $this->assertInstanceOf('Kint\\Zval\\ElidedValues', $elide);
        $this->assertSame(\count($v) - ArrayLimitPlugin::$limit, $elide->size);
        $this->assertSame(\array_slice(\array_keys($v), ArrayLimitPlugin::$limit), $elide->description);

        $v['key'] = 'val';
        $completed = false;
        $o = $p->parse($v, clone $b);

        $this->assertCount(\count($v), $o->value->contents);
        $this->assertTrue($completed);

        ArrayLimitPlugin::$numeric_only = false;

        $completed = false;
        $o = $p->parse($v, clone $b);

        $this->assertCount(ArrayLimitPlugin::$limit + 1, $o->value->contents);
        $this->assertFalse($completed);

        $elide = \end($o->value->contents);
        $this->assertInstanceOf('Kint\\Zval\\ElidedValues', $elide);
        $this->assertSame(\count($v) - ArrayLimitPlugin::$limit, $elide->size);
        $this->assertSame(\array_slice(\array_keys($v), ArrayLimitPlugin::$limit), $elide->description);
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parse
     */
    public function testInvalidSettings()
    {
        $this->expectException('InvalidArgumentException');

        ArrayLimitPlugin::$trigger = 20;
        ArrayLimitPlugin::$limit = 30;

        $alp = new ArrayLimitPlugin();
        $b = Value::blank('$v', '$v');
        $v = \range(1, 200);
        $alp->parse($v, $b, Parser::TRIGGER_BEGIN);
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parse
     */
    public function testInvalidParsedValue()
    {
        $alp = new ArrayLimitPlugin();
        $parser = $this->prophesize('Kint\\Parser\\Parser');
        $alp->setParser($parser->reveal());
        $b = Value::blank('$v', '$v');
        $v = \range(1, 200);

        $badObj = new Value();
        $badObj->type = 'integer';
        $parser
            ->parse(
                Argument::that(function ($v) {
                    return \is_array($v) && \count($v) == ArrayLimitPlugin::$limit;
                }),
                Argument::type('Kint\\Zval\\Value')
            )
            ->shouldBeCalledTimes(1)
            ->willReturn($badObj);

        $alp->parse($v, $b, Parser::TRIGGER_BEGIN);
    }
}
