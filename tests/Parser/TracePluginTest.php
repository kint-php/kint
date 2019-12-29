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
use Kint\Parser\Parser;
use Kint\Parser\TracePlugin;
use PHPUnit\Framework\TestCase;

class TracePluginTest extends TestCase
{
    protected $blacklist_stash;

    protected function setUp()
    {
        $this->blacklist_stash = TracePlugin::$blacklist;
    }

    protected function tearDown()
    {
        TracePlugin::$blacklist = $this->blacklist_stash;
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parse
     */
    public function testParse()
    {
        $p = new Parser();
        $p->addPlugin(new TracePlugin());

        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $o = BasicObject::blank();

        $o = $p->parse($bt, $o);

        $this->assertContains('trace', $o->hints);
        $this->assertInstanceOf('Kint\\Object\\TraceObject', $o);
        $this->assertInstanceOf('Kint\\Object\\TraceFrameObject', $o->value->contents[0]);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parse
     */
    public function testParseMismatch()
    {
        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $b = BasicObject::blank();
        $parser = new Parser();
        $plugin = new TracePlugin();

        $incorrect = $parser->parse($bt, clone $b);
        $incorrect->value->contents[0]->name = 'newName';
        $parser->addPlugin($plugin);
        $plugin->parse($bt, $incorrect, Parser::TRIGGER_SUCCESS);

        \array_shift($bt);
        $correct = $parser->parse($bt, clone $b);

        foreach ($correct->value->contents as $frame) {
            ++$frame->name;
        }

        $this->assertEquals($correct, $incorrect);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parse
     */
    public function testParseNoValue()
    {
        $p = new TracePlugin();

        $b = BasicObject::blank();
        $o = clone $b;
        $v = [];

        $p->parse($v, $o, Parser::TRIGGER_SUCCESS);

        $this->assertEquals($b, $o);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parse
     */
    public function testParseBlacklist()
    {
        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $shortbt = $bt;
        \array_shift($shortbt);

        $p = new Parser();
        $p->addPlugin(new TracePlugin());

        $b = BasicObject::blank();

        $o = $p->parse($shortbt, clone $b);

        foreach ($o->value->contents as $frame) {
            ++$frame->name;
        }

        TracePlugin::$blacklist[] = [__CLASS__, __FUNCTION__];

        $this->assertEquals($o->value, $p->parse($bt, clone $b)->value);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::getTriggers
     * @covers \Kint\Parser\TracePlugin::getTypes
     */
    public function testHooks()
    {
        $p = new TracePlugin();

        $this->assertSame(['array'], $p->getTypes());
        $this->assertSame(Parser::TRIGGER_SUCCESS, $p->getTriggers());
    }
}
