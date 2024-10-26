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

use Kint\Parser\BlacklistPlugin;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\InstanceValue;
use stdClass;

/**
 * @coversNothing
 */
class BlacklistPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\BlacklistPlugin::getTypes
     */
    public function testGetTypes()
    {
        $b = new BlacklistPlugin($this->createStub(Parser::class));

        $this->assertSame(['object'], $b->getTypes());
    }

    /**
     * @covers \Kint\Parser\BlacklistPlugin::getTriggers
     */
    public function testGetTriggers()
    {
        $b = new BlacklistPlugin($this->createStub(Parser::class));

        $this->assertSame(Parser::TRIGGER_BEGIN, $b->getTriggers());
    }

    /**
     * @covers \Kint\Parser\BlacklistPlugin::blacklistValue
     * @covers \Kint\Parser\BlacklistPlugin::parseBegin
     */
    public function testBlacklistValue()
    {
        $p = new Parser();
        $bp = new BlacklistPlugin($p);
        $b = new BaseContext('$v');
        $v = new ChildTestClass();

        $p->addPlugin($bp);

        $completed = false;
        $pp = new ProxyPlugin(
            ['object'],
            Parser::TRIGGER_COMPLETE,
            function (&$var, $v) use (&$completed) {
                $completed = true;

                return $v;
            }
        );

        $p->addPlugin($pp);

        $o = $p->parse($v, clone $b);

        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertTrue($completed);

        BlacklistPlugin::$shallow_blacklist[] = TestClass::class;

        $completed = false;
        $o = $p->parse($v, clone $b);

        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertTrue($completed);

        $v = [$v];

        $completed = false;
        $bo = $p->parse($v, clone $b);
        $bo = $bo->getContents();
        $bo = \reset($bo);

        $this->assertEquals(true, $bo->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertFalse($completed);
        $this->assertInstanceOf(InstanceValue::class, $bo);
        $this->assertSame($o->getSplObjectHash(), $bo->getSplObjectHash());
        $this->assertSame($o->getClassName(), $bo->getClassName());

        $v = \reset($v);
        BlacklistPlugin::$blacklist[] = TestClass::class;

        $completed = false;
        $bo = $p->parse($v, clone $b);

        $this->assertEquals(true, $bo->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertFalse($completed);
        $this->assertEquals($o->getContext(), $bo->getContext());
        $this->assertSame($o->getSplObjectHash(), $bo->getSplObjectHash());
        $this->assertSame($o->getClassName(), $bo->getClassName());

        $v = new stdClass();

        $completed = false;
        $o = $p->parse($v, clone $b);

        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertTrue($completed);

        $v = [$v];

        $completed = false;
        $o = $p->parse($v, clone $b);
        $o = $o->getContents();
        $o = \reset($o);

        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_BLACKLIST);
        $this->assertTrue($completed);
    }

    /**
     * @covers \Kint\Parser\BlacklistPlugin::parseBegin
     */
    public function testBadParse()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $bp = new BlacklistPlugin($p);

        $p->addPlugin($bp);

        $o = $bp->parseBegin($v, $b, Parser::TRIGGER_BEGIN);

        $this->assertNull($o);
    }
}
