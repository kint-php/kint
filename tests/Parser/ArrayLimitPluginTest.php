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
use Kint\Parser\ProxyPlugin;
use Kint\Test\KintTestCase;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
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
     * @covers \Kint\Parser\ArrayLimitPlugin::parse
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParse()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);
        $b = new Value('$v');
        $v = $this->makeValueArray();

        $p->addPlugin($alp);

        $o = $p->parse($v, clone $b);

        $this->assertCount(\count($v), $o->value->contents);

        $result = \array_map(
            function ($item) {
                return $item->name;
            },
            $o->value->contents
        );

        $this->assertSame(\array_keys($v), $result);

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $o = $p->parse($v, clone $b);

        $this->assertSame(0, $o->depth);
        $this->assertSame(\count($v), $o->size);
        $this->assertCount(\count($v), $o->value->contents);

        $result = \array_map(
            function ($item) {
                return $item->name;
            },
            $o->value->contents
        );

        $this->assertSame(\array_keys($v), $result);

        $this->assertArrayNotHasKey('array_limit', $o->value->contents[18]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[19]->hints);
        $this->assertArrayHasKey('array_limit', $o->value->contents[20]->hints);
        $this->assertArrayHasKey('array_limit', $o->value->contents[21]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[22]->hints);
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parse
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseNoDepthLimit()
    {
        $p = new Parser(0);
        $alp = new ArrayLimitPlugin($p);
        $b = new Value('$v');
        $v = $this->makeValueArray();

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $p->addPlugin($alp);

        $o = $p->parse($v, clone $b);

        $this->assertArrayNotHasKey('array_limit', $o->value->contents[18]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[19]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[20]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[21]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[22]->hints);
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parse
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseRecurse()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);
        $b = new Value('$v');
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

        $this->assertArrayNotHasKey('recursion', $o->value->contents[0]->hints);
        $this->assertArrayHasKey('recursion', $o->value->contents[0]->value->contents[0]->hints);

        $this->assertArrayNotHasKey('array_limit', $o->value->contents[18]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[19]->hints);
        $this->assertArrayHasKey('array_limit', $o->value->contents[20]->hints);
        $this->assertArrayHasKey('array_limit', $o->value->contents[21]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[22]->hints);

        $this->assertArrayNotHasKey('recursion', \end($o->value->contents)->hints);
        $this->assertArrayHasKey('array_limit', \end($o->value->contents)->hints);
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parse
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseAssoc()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);
        $b = new Value('$v');
        $v = $this->makeValueArray();

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $p->addPlugin($alp);

        $o = $p->parse($v, clone $b);

        $i = 0;

        $this->assertArrayNotHasKey('array_limit', $o->value->contents[18]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[19]->hints);
        $this->assertArrayHasKey('array_limit', $o->value->contents[20]->hints);
        $this->assertArrayHasKey('array_limit', $o->value->contents[21]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[22]->hints);

        $v['test'] = 'val';

        $o = $p->parse($v, clone $b);

        $this->assertArrayNotHasKey('array_limit', $o->value->contents[18]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[19]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[20]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[21]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[22]->hints);

        ArrayLimitPlugin::$numeric_only = false;

        $o = $p->parse($v, clone $b);

        $this->assertArrayNotHasKey('array_limit', $o->value->contents[18]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[19]->hints);
        $this->assertArrayHasKey('array_limit', $o->value->contents[20]->hints);
        $this->assertArrayHasKey('array_limit', $o->value->contents[21]->hints);
        $this->assertArrayNotHasKey('array_limit', $o->value->contents[22]->hints);
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testParseManipulated()
    {
        $p = new Parser(5);
        $alp = new ArrayLimitPlugin($p);
        $b = new Value('$v');
        $v = $this->makeValueArray();
        $subv = ['test' => 'val', 'array' => ['1', 2, 'three']];
        $v[] = \json_encode($subv);

        ArrayLimitPlugin::$trigger = 50;
        ArrayLimitPlugin::$limit = 20;

        $p->addPlugin($alp);
        $p->addPlugin(new JsonPlugin($p));

        $o = $p->parse($v, clone $b);

        // Test JSON string
        $subv = \end($o->value->contents);
        $this->assertArrayNotHasKey('array_limit', $subv->hints);
        $subv = $subv->getRepresentation('json');
        $this->assertInstanceOf(Representation::class, $subv);
        // array
        $subv = $subv->contents;
        $this->assertInstanceOf(Value::class, $subv);
        $this->assertArrayHasKey('array_limit', $subv->hints);

        // Testing manipulated topography with arrays as representation contents
        $p = new Parser(5);
        $p->addPlugin($alp);
        $p->addPlugin(new ProxyPlugin(['string'], Parser::TRIGGER_SUCCESS, function (&$var, &$o, $trigger, $parser) {
            $v = \json_decode($var);
            if (!$v) {
                return;
            }

            $jp = new JsonPlugin($parser);
            $jp->parse($var, $o, $trigger);

            // Wrap the array
            $r = $o->getRepresentation('json');
            $r->contents = [$r->contents];
        }));

        $o = $p->parse($v, clone $b);

        $subv = \end($o->value->contents);
        $this->assertArrayNotHasKey('array_limit', $subv->hints);
        $subv = $subv->getRepresentation('json');
        $this->assertInstanceOf(Representation::class, $subv);
        // wrapped array
        $subv = $subv->contents;
        $this->assertSame('array', \gettype($subv));
        $this->assertCount(1, $subv);
        // array
        $subv = \reset($subv);
        $this->assertInstanceOf(Value::class, $subv);
        $this->assertArrayHasKey('array_limit', $subv->hints);
    }

    /**
     * @covers \Kint\Parser\ArrayLimitPlugin::parse
     * @covers \Kint\Parser\ArrayLimitPlugin::replaceDepthLimit
     */
    public function testInvalidSettings()
    {
        $this->expectException(InvalidArgumentException::class);

        ArrayLimitPlugin::$trigger = 20;
        ArrayLimitPlugin::$limit = 30;

        $alp = new ArrayLimitPlugin($this->createStub(Parser::class));
        $b = new Value('$v');
        $v = \range(1, 200);
        $alp->parse($v, $b, Parser::TRIGGER_BEGIN);
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
        $v = \call_user_func_array('array_merge', \array_fill(0, 20, $v));
        $v[99999] = 'string';
        $v[] = ['item 1', 'item 2'];

        return $v;
    }
}
