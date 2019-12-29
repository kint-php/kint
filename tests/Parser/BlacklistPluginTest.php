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

use Kint\Object\BasicObject;
use Kint\Parser\BlacklistPlugin;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\KintTestCase;
use stdClass;

class BlacklistPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\BlacklistPlugin::getTypes
     */
    public function testGetTypes()
    {
        $b = new BlacklistPlugin();

        $this->assertContains('array', $b->getTypes());
        $this->assertContains('object', $b->getTypes());
    }

    /**
     * @covers \Kint\Parser\BlacklistPlugin::getTriggers
     */
    public function testGetTriggers()
    {
        $b = new BlacklistPlugin();

        $this->assertSame(Parser::TRIGGER_BEGIN, $b->getTriggers());
    }

    /**
     * @covers \Kint\Parser\BlacklistPlugin::blacklistObject
     * @covers \Kint\Parser\BlacklistPlugin::parse
     * @covers \Kint\Parser\BlacklistPlugin::parseObject
     */
    public function testBlacklistObject()
    {
        $p = new Parser();
        $bp = new BlacklistPlugin();
        $b = BasicObject::blank('$v', '$v');
        $v = new ChildTestClass();

        $p->addPlugin($bp);

        $completed = false;
        $pp = new ProxyPlugin(
            ['object'],
            Parser::TRIGGER_COMPLETE,
            function () use (&$completed) {
                $completed = true;
            }
        );

        $p->addPlugin($pp);

        $o = $p->parse($v, clone $b);

        $this->assertNotContains('blacklist', $o->hints);
        $this->assertTrue($completed);

        BlacklistPlugin::$shallow_blacklist[] = 'Kint\\Test\\Fixtures\\TestClass';

        $completed = false;
        $o = $p->parse($v, clone $b);

        $this->assertNotContains('blacklist', $o->hints);
        $this->assertTrue($completed);

        $v = [$v];

        $completed = false;
        $bo = $p->parse($v, clone $b);

        $bo = \reset($bo->value->contents);

        $this->assertContains('blacklist', $bo->hints);
        $this->assertFalse($completed);
        $this->assertInstanceOf('Kint\\Object\\InstanceObject', $bo);
        $this->assertSame($o->hash, $bo->hash);
        $this->assertSame($o->classname, $bo->classname);

        $v = \reset($v);
        BlacklistPlugin::$blacklist[] = 'Kint\\Test\\Fixtures\\TestClass';

        $completed = false;
        $bo = $p->parse($v, clone $b);

        $this->assertContains('blacklist', $bo->hints);
        $this->assertFalse($completed);
        $this->assertSame($o->name, $bo->name);
        $this->assertSame($o->access_path, $bo->access_path);
        $this->assertSame($o->hash, $bo->hash);
        $this->assertSame($o->classname, $bo->classname);

        $v = new stdClass();

        $completed = false;
        $o = $p->parse($v, clone $b);

        $this->assertNotContains('blacklist', $o->hints);
        $this->assertTrue($completed);

        $v = [$v];

        $completed = false;
        $o = $p->parse($v, clone $b);

        $o = \reset($o->value->contents);

        $this->assertNotContains('blacklist', $o->hints);
        $this->assertTrue($completed);
    }

    /**
     * @covers \Kint\Parser\BlacklistPlugin::blacklistArray
     * @covers \Kint\Parser\BlacklistPlugin::parse
     * @covers \Kint\Parser\BlacklistPlugin::parseArray
     */
    public function testBlacklistArray()
    {
        $p = new Parser();
        $bp = new BlacklistPlugin();
        $b = BasicObject::blank('$v', '$v');
        $v = \range(1, 100);

        $p->addPlugin($bp);

        $completed = false;
        $pp = new ProxyPlugin(
            ['array'],
            Parser::TRIGGER_COMPLETE,
            function () use (&$completed) {
                $completed = true;
            }
        );

        $p->addPlugin($pp);

        $o = $p->parse($v, clone $b);

        $this->assertNotContains('blacklist', $o->hints);
        $this->assertTrue($completed);

        BlacklistPlugin::$shallow_array_limit = 10;

        $completed = false;
        $o = $p->parse($v, clone $b);

        $this->assertNotContains('blacklist', $o->hints);
        $this->assertTrue($completed);

        $v = (object) [$v];

        $completed = false;
        $bo = $p->parse($v, clone $b);

        $bo = \reset($bo->value->contents);

        $this->assertContains('blacklist', $bo->hints);
        $this->assertFalse($completed);
        $this->assertSame('array', $bo->type);

        $v = \range(1, 100);
        BlacklistPlugin::$array_limit = 20;

        $completed = false;
        $bo = $p->parse($v, clone $b);

        $this->assertContains('blacklist', $bo->hints);
        $this->assertFalse($completed);
        $this->assertSame('array', $bo->type);
        $this->assertSame($o->name, $bo->name);
        $this->assertSame($o->access_path, $bo->access_path);

        $v = \range(1, 20);

        $completed = false;
        $o = $p->parse($v, clone $b);

        $this->assertNotContains('blacklist', $o->hints);
        $this->assertTrue($completed);

        $v = [\range(1, 10)];

        $completed = false;
        $o = $p->parse($v, clone $b);

        $o = \reset($o->value->contents);

        $this->assertNotContains('blacklist', $o->hints);
        $this->assertTrue($completed);
    }

    /**
     * @covers \Kint\Parser\BlacklistPlugin::parse
     */
    public function testBadParse()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v', '$v');
        $v = 1234;

        $o = $p->parse($v, clone $b);
        $ostash = clone $o;

        $bp = new BlacklistPlugin();

        $p->addPlugin($bp);

        $bp->parse($v, $o, Parser::TRIGGER_BEGIN);

        $this->assertSame(1234, $v);
        $this->assertEquals($ostash, $o);
    }
}
