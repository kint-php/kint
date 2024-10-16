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

use Kint\Parser\Parser;
use Kint\Parser\TracePlugin;
use Kint\Test\KintTestCase;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\TraceFrameValue;
use Kint\Zval\TraceValue;
use Kint\Zval\Value;

/**
 * @coversNothing
 */
class TracePluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\TracePlugin::parseComplete
     */
    public function testParse()
    {
        $p = new Parser();
        $p->addPlugin(new TracePlugin($p));

        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $o = new BaseContext('$bt');

        $o = $p->parse($bt, $o);

        $this->assertArrayHasKey('trace', $o->hints);
        $this->assertInstanceOf(TraceValue::class, $o);
        $this->assertInstanceOf(TraceFrameValue::class, $o->value->contents[0]);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parseComplete
     */
    public function testParseDepths()
    {
        $p = new Parser(3);
        $p->addPlugin(new TracePlugin($p));

        $base = new BaseContext('$bt');
        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $o = $p->parse($bt, clone $base);

        $this->assertArrayHasKey('trace', $o->hints);
        $this->assertInstanceOf(TraceValue::class, $o);
        $this->assertInstanceOf(TraceFrameValue::class, $o->value->contents[0]);

        $p->setDepthLimit(2);

        $o = $p->parse($bt, clone $base);

        $this->assertArrayNotHasKey('trace', $o->hints);
        $this->assertNotInstanceOf(TraceValue::class, $o);
        $this->assertNotInstanceOf(TraceFrameValue::class, $o->value->contents[0]);
    }

    /**
     * Tests to ensure frames that have modified mismatching names compared to
     * the original array indices are not included in the backtrace.
     *
     * @covers \Kint\Parser\TracePlugin::parseComplete
     */
    public function testParseMismatch()
    {
        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $b = new BaseContext('$bt');
        $parser = new Parser();
        $plugin = new TracePlugin($parser);

        $incorrect = $parser->parse($bt, clone $b);
        $incorrect->value->contents[0]->getContext()->name = 'newName';
        $parser->addPlugin($plugin);
        $incorrect = $plugin->parseComplete($bt, $incorrect, Parser::TRIGGER_SUCCESS);

        \array_shift($bt);
        $correct = $parser->parse($bt, clone $b);

        foreach ($correct->value->contents as $frame) {
            ++$frame->getContext()->name;
        }

        $this->assertEquals($correct, $incorrect);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parseComplete
     */
    public function testParseNoValue()
    {
        $p = new TracePlugin($this->createStub(Parser::class));

        $b = new Value(new BaseContext('$v'));
        $v = [];

        $o = $p->parseComplete($v, clone $b, Parser::TRIGGER_SUCCESS);

        $this->assertEquals($b, $o);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parseComplete
     */
    public function testParseBlacklist()
    {
        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $shortbt = $bt;
        \array_shift($shortbt);

        $p = new Parser();
        $p->addPlugin(new TracePlugin($p));

        $b = new BaseContext('$shortbt');

        $o = $p->parse($shortbt, clone $b);

        foreach ($o->value->contents as $frame) {
            ++$frame->getContext()->name;
        }

        TracePlugin::$blacklist[] = [__CLASS__, __FUNCTION__];

        $this->assertEquals($o->value, $p->parse($bt, clone $b)->value);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parseComplete
     * @covers \Kint\Parser\TracePlugin::normalizePaths
     */
    public function testParsePathBlacklist()
    {
        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $shortbt = $bt;
        foreach ($shortbt as $index => $frame) {
            if (isset($frame['file']) && __FILE__ == $frame['file']) {
                unset($shortbt[$index]);
            }
        }

        $p = new Parser();
        $p->addPlugin(new TracePlugin($p));

        $b = new BaseContext('$shortbt');

        $o = $p->parse($shortbt, clone $b);

        TracePlugin::$path_blacklist[] = __FILE__;

        $this->assertEquals($o->value, $p->parse($bt, clone $b)->value);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parseComplete
     * @covers \Kint\Parser\TracePlugin::normalizePaths
     */
    public function testParsePathBlacklistFolder()
    {
        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $p = new Parser();
        $p->addPlugin(new TracePlugin($p));

        $b = new BaseContext('$bt');

        $blacklist = \realpath(__DIR__.'/../../vendor');

        $hasVendor = false;
        $o = $p->parse($bt, clone $b);
        foreach ($o->value->contents as $frame) {
            foreach ($frame->value->contents as $prop) {
                if ('file' === $prop->getContext()->getName() && false !== \strpos($prop->value->contents, $blacklist)) {
                    $hasVendor = true;
                    break 2;
                }
            }
        }
        $this->assertTrue($hasVendor);

        TracePlugin::$path_blacklist[] = $blacklist;

        $hasVendor = false;
        $o = $p->parse($bt, clone $b);
        foreach ($o->value->contents as $frame) {
            foreach ($frame->value->contents as $prop) {
                // Note: We check against 0 to ignore certain malformed GHA paths that start with a schema
                if ('file' === $prop->getContext()->getName() && 0 === \strpos($prop->value->contents, $blacklist)) {
                    $hasVendor = true;
                    break 2;
                }
            }
        }
        $this->assertFalse($hasVendor);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::getTriggers
     * @covers \Kint\Parser\TracePlugin::getTypes
     */
    public function testHooks()
    {
        $p = new TracePlugin($this->createStub(Parser::class));

        $this->assertSame(['array'], $p->getTypes());
        $this->assertSame(Parser::TRIGGER_SUCCESS, $p->getTriggers());
    }
}
