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

use InvalidArgumentException;
use Kint\Parser\ArrayLimitPlugin;
use Kint\Parser\JsonPlugin;
use Kint\Parser\Parser;
use Kint\Parser\ProfilePlugin;
use Kint\Parser\ProxyPlugin;
use Kint\Test\KintTestCase;
use Kint\Zval\AbstractValue;
use Kint\Zval\ArrayValue;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\FixedWidthValue;
use Kint\Zval\Representation\Representation;
use stdClass;

/**
 * @coversNothing
 */
class ArrayLimitPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::getTypes
     */
    public function testGetTypes()
    {
        $a = new ArrayLimitPlugin($this->createStub(Parser::class));

        $this->assertSame(['array'], $a->getTypes());
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::getTriggers
     */
    public function testGetTriggers()
    {
        $a = new ArrayLimitPlugin($this->createStub(Parser::class));

        $this->assertSame(Parser::TRIGGER_BEGIN, $a->getTriggers());
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parseBegin
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParse()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);
        $b = new BaseContext('$v');
        $v = $this->makeValueArray();

        $p->addPlugin($alp);

        $o = $p->parse($v, clone $b);

        $this->assertSame(\count($v), $o->getSize());
        $this->assertCount(\count($v), $o->getContents());

        $result = \array_values(\array_map(
            function ($item) {
                return $item->getContext()->getName();
            },
            $o->getContents()
        ));

        $this->assertSame(\array_keys($v), $result);

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $o = $p->parse($v, clone $b);

        $this->assertSame(0, $o->getContext()->getDepth());
        $this->assertSame(\count($v), $o->getSize());
        $this->assertCount(\count($v), $o->getContents());

        $result = \array_values(\array_map(
            function ($item) {
                return $item->getContext()->getName();
            },
            $o->getContents()
        ));

        $this->assertSame(\array_keys($v), $result);

        $this->assertFalse($o->getContents()[18]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[19]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[20]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[21]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[22]->hasHint('array_limit'));
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parseBegin
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseNoDepthLimit()
    {
        $p = new Parser(0);
        $alp = new ArrayLimitPlugin($p);
        $b = new BaseContext('$v');
        $v = $this->makeValueArray();

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $p->addPlugin($alp);

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->getContents()[18]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[19]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[20]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[21]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[22]->hasHint('array_limit'));
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parseBegin
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseRecurse()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);
        $b = new BaseContext('$v');
        $v = $this->makeValueArray();
        $v[0] = [];
        $v[0][] = &$v[0];
        $v['last'] = [];
        $v['last'][] = &$v['last'];

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;
        ArrayLimitPlugin::$numeric_only = false;

        $p->addPlugin($alp);

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->getContents()[0]->hasHint('recursion'));
        $this->assertTrue($o->getContents()[0]->getContents()[0]->hasHint('recursion'));

        $this->assertFalse($o->getContents()[18]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[19]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[20]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[21]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[22]->hasHint('array_limit'));

        $last = $o->getContents();
        $last = \end($last);

        $this->assertFalse($last->hasHint('recursion'));
        $this->assertTrue($last->hasHint('array_limit'));
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parseBegin
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseAssoc()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);
        $b = new BaseContext('$v');
        $v = $this->makeValueArray();

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $p->addPlugin($alp);

        $o = $p->parse($v, clone $b);

        $i = 0;

        $this->assertFalse($o->getContents()[18]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[19]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[20]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[21]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[22]->hasHint('array_limit'));

        $v['test'] = 'val';

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->getContents()[18]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[19]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[20]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[21]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[22]->hasHint('array_limit'));

        ArrayLimitPlugin::$numeric_only = false;

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->getContents()[18]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[19]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[20]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[21]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[22]->hasHint('array_limit'));
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parseBegin
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseDetachedValue()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);

        $pp = new ProxyPlugin(['array'], Parser::TRIGGER_DEPTH_LIMIT, function (&$var, $v) {
            $v->removeRepresentation('contents');

            return $v;
        });

        $p->addPlugin($alp);
        $p->addPlugin($pp);

        $b = new BaseContext('$v');
        $v = $this->makeValueArray();

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->getContents()[18]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[19]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[20]->hasHint('array_limit'));
        $this->assertTrue($o->getContents()[21]->hasHint('array_limit'));
        $this->assertFalse($o->getContents()[22]->hasHint('array_limit'));
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseManipulated()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);
        $b = new BaseContext('$v');
        $v = $this->makeValueArray();
        $subv = ['test' => 'val', 'array' => ['1', 2, 'three']];
        $v[] = \json_encode($subv);

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $p->addPlugin($alp);
        $p->addPlugin(new JsonPlugin($p));

        $o = $p->parse($v, clone $b);

        // Test JSON string
        $subv = $o->getContents();
        $subv = \end($subv);
        $this->assertFalse($subv->hasHint('array_limit'));
        $subv = $subv->getRepresentation('json');
        $this->assertInstanceOf(Representation::class, $subv);
        // array
        $subv = $subv->contents;
        $this->assertInstanceOf(AbstractValue::class, $subv);
        $this->assertTrue($subv->hasHint('array_limit'));

        // Testing manipulated topography with arrays as representation contents
        $p = new Parser(5);
        $p->addPlugin($alp);
        $p->addPlugin(new ProxyPlugin(['string'], Parser::TRIGGER_SUCCESS, function (&$var, $o, $trigger, $parser) {
            $data = \json_decode($var);
            if (!$data) {
                return $o;
            }

            $jp = new JsonPlugin($parser);
            $jp->parseComplete($var, $o, $trigger);

            // Wrap the array
            $r = $o->getRepresentation('json');
            $r->contents = [$r->contents];

            return $o;
        }));

        $o = $p->parse($v, clone $b);

        $subv = $o->getContents();
        $subv = \end($subv);
        $this->assertFalse($subv->hasHint('array_limit'));
        $subv = $subv->getRepresentation('json');
        $this->assertInstanceOf(Representation::class, $subv);
        // wrapped array
        $subv = $subv->contents;
        $this->assertSame('array', \gettype($subv));
        $this->assertCount(1, $subv);
        // array
        $subv = \reset($subv);
        $this->assertInstanceOf(AbstractValue::class, $subv);
        $this->assertTrue($subv->hasHint('array_limit'));
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parseBegin
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseClearRepresentations()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);

        $pp = new ProxyPlugin(['array'], Parser::TRIGGER_COMPLETE, function (&$var, $v) {
            $v->addRepresentation(new Representation('Test if this is removed', 'test_remove'));

            return $v;
        });

        $p->addPlugin($alp);
        $p->addPlugin($pp);
        $p->addPlugin(new ProfilePlugin($p));

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = $this->makeValueArray();

        $o = $p->parse($v, clone $b);

        $this->assertCount(4, $o->getRepresentations());
        $this->assertArrayHasKey('profiling', $o->getRepresentations());
        $this->assertArrayHasKey('class_complexity', $o->getRepresentations());
        $this->assertArrayHasKey('contents', $o->getRepresentations());
        $this->assertArrayHasKey('test_remove', $o->getRepresentations());

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $o = $p->parse($v, clone $b);

        $this->assertCount(2, $o->getRepresentations());
        $this->assertArrayHasKey('profiling', $o->getRepresentations());
        $this->assertArrayHasKey('contents', $o->getRepresentations());
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parseBegin
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testBadParse()
    {
        $b = new BaseContext('$v');
        $v = $this->makeValueArray();

        $out = new FixedWidthValue($b, 1);
        $out2 = new ArrayValue($b, 1, []);

        $parser = $this->createMock(Parser::class);
        $parser->method('getDepthLimit')->willReturn(5);
        $parser->expects($this->exactly(3))->method('parse')->willReturnOnConsecutiveCalls($out, $out2, $out);

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $p = new ArrayLimitPlugin($parser);

        $o = $p->parseBegin($v, $b, Parser::TRIGGER_BEGIN);

        $this->assertNull($o);

        $o = $p->parseBegin($v, $b, Parser::TRIGGER_BEGIN);

        $this->assertNull($o);
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parseBegin
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseSeparateValue()
    {
        $b = new BaseContext('$v');

        $contents = [];
        $contents2 = [];

        for ($i = 0; $i < 30; ++$i) {
            $v = new FixedWidthValue(new BaseContext('item'), 123);
            $contents[] = $v;

            $v = new FixedWidthValue(new BaseContext('item'), 123);
            $v->addHint('depth_limit');
            $contents2[] = $v;
        }

        $out = new ArrayValue($b, 30, $contents);
        $out2 = new ArrayValue($b, 30, $contents2);

        $parser = $this->createMock(Parser::class);
        $parser->method('getDepthLimit')->willReturn(3);
        $parser->expects($this->exactly(2))->method('parse')->willReturnOnConsecutiveCalls($out, $out2);

        $v = $this->makeValueArray();

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 30;

        $p = new ArrayLimitPlugin($parser);
        $p->setParser($parser);

        $o = $p->parseBegin($v, $b, Parser::TRIGGER_BEGIN);

        foreach ($o->getContents() as $index => $val) {
            if ($index >= 30) {
                $this->assertTrue($val->hasHint('array_limit'));
            } else {
                $this->assertFalse($val->hasHint('array_limit'));
            }
        }
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parseBegin
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testInvalidSettings()
    {
        $this->expectException(InvalidArgumentException::class);

        ArrayLimitPlugin::$trigger = 20;
        ArrayLimitPlugin::$limit = 30;

        $alp = new ArrayLimitPlugin($this->createStub(Parser::class));
        $b = new BaseContext('$v');
        $v = \range(1, 200);
        $alp->parseBegin($v, $b, 'array');
    }

    private function makeValueArray()
    {
        $v = new stdClass();
        $v->item1 = 1;
        $v->item2 = 2;
        $v = [
            ['item 1', 'item 2'],
            'string',
            $v,
        ];
        $v = \array_merge(...\array_fill(0, 20, $v));
        $v[99999] = 'string';
        $v[] = ['item 1', 'item 2'];

        return $v;
    }
}
