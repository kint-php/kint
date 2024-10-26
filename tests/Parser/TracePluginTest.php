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
use Kint\Parser\ProxyPlugin;
use Kint\Parser\TracePlugin;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\ArrayValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\FixedWidthValue;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\SourceRepresentation;
use Kint\Value\TraceFrameValue;
use Kint\Value\TraceValue;

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

        $bt = (new TestClass())->getMeATrace();

        foreach ($bt as $index => &$frame) {
            if ($index > 0) {
                unset($frame['object']);
            }
            unset($frame['args']);
        }

        $o = new BaseContext('$bt');

        $o = $p->parse($bt, $o);

        $this->assertSame('trace', $o->getHint());
        $this->assertInstanceOf(TraceValue::class, $o);
        $this->assertInstanceOf(TraceFrameValue::class, $o->getContents()[0]);
        $this->assertInstanceOf(InstanceValue::class, $o->getContents()[0]->getObject());
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

        $this->assertSame('trace', $o->getHint());
        $this->assertInstanceOf(TraceValue::class, $o);
        $this->assertInstanceOf(TraceFrameValue::class, $o->getContents()[0]);

        $p->setDepthLimit(2);

        $o = $p->parse($bt, clone $base);

        $this->assertNotSame('trace', $o->getHint());
        $this->assertNotInstanceOf(TraceValue::class, $o);
        $this->assertNotInstanceOf(TraceFrameValue::class, $o->getContents()[0]);
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

        $incorrect->getContents()[0]->getContext()->name = 'newName';
        $parser->addPlugin($plugin);
        $incorrect = $plugin->parseComplete($bt, $incorrect, Parser::TRIGGER_SUCCESS);

        \array_shift($bt);
        $correct = $parser->parse($bt, clone $b);

        foreach ($correct->getContents() as $frame) {
            ++$frame->getContext()->name;
        }

        $this->assertEquals(
            \array_values($correct->getContents()),
            \array_values($incorrect->getContents())
        );
    }

    /**
     * Tests to ensure frames are skipped if somehow not parsed as ArrayValue.
     *
     * @covers \Kint\Parser\TracePlugin::parseComplete
     */
    public function testParseSubMismatch()
    {
        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $b = new BaseContext('$bt');
        $parser = new Parser();
        $plugin = new TracePlugin($parser);

        $change_value = true;

        $pp = new ProxyPlugin(
            ['array'],
            Parser::TRIGGER_COMPLETE,
            function (&$var, $o) use (&$change_value) {
                if ($o->getContext()->getDepth() > 0 && $change_value) {
                    $change_value = false;

                    return new FixedWidthValue($o->getContext(), null);
                }

                return $o;
            }
        );

        $original = $parser->parse($bt, clone $b);

        $parser->addPlugin($pp);
        $parser->addPlugin($plugin);

        $changed = $parser->parse($bt, clone $b);

        $this->assertNotEquals($original, $changed);
        $this->assertCount($original->getSize() - 1, $changed->getContents());
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parseComplete
     */
    public function testParseNoValue()
    {
        $p = new TracePlugin($this->createStub(Parser::class));

        $b = new ArrayValue(new BaseContext('$v'), 0, []);
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

        foreach ($o->getContents() as $frame) {
            ++$frame->getContext()->name;
        }

        TracePlugin::$blacklist[] = [__CLASS__, __FUNCTION__];

        $this->assertEquals(
            \array_values($o->getContents()),
            \array_values($p->parse($bt, clone $b)->getContents())
        );
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

        $this->assertEquals($o->getContents(), $p->parse($bt, clone $b)->getContents());
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
        foreach ($o->getContents() as $frame) {
            if (0 === \strpos($frame->getFile(), $blacklist)) {
                $hasVendor = true;
                break;
            }
        }
        $this->assertTrue($hasVendor);

        TracePlugin::$path_blacklist[] = $blacklist;

        $hasVendor = false;
        $o = $p->parse($bt, clone $b);
        foreach ($o->getContents() as $frame) {
            if (0 === \strpos($frame->getFile(), $blacklist)) {
                $hasVendor = true;
                break;
            }
        }
        $this->assertFalse($hasVendor);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parseComplete
     */
    public function testParseBadValue()
    {
        $p = new Parser();
        $t = new TracePlugin($p);
        $p->addPlugin($t);

        $v = 123;
        $b = new BaseContext('$v');
        $o = new FixedWidthValue($b, $v);

        $out = $t->parseComplete($v, $o, Parser::TRIGGER_SUCCESS);

        $this->assertSame($o, $out);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parseComplete
     */
    public function testParseBadSource()
    {
        $p = new Parser();
        $p->addPlugin(new TracePlugin($p));
        $b = new BaseContext('$bt');
        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $o = $p->parse($bt, clone $b);

        $this->assertInstanceOf(SourceRepresentation::class, $o->getContents()[0]->getRepresentation('source'));

        $bt[0]['line'] = 999999999;

        $o = $p->parse($bt, clone $b);

        $this->assertNull($o->getContents()[0]->getRepresentation('source'));
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
