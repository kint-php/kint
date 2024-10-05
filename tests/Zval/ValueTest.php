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

namespace Kint\Test\Zval;

use Kint\Test\KintTestCase;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

/**
 * @coversNothing
 */
class ValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Zval\Value::__construct
     * @covers \Kint\Zval\Value::getContext
     */
    public function testConstruct()
    {
        $v = new Value($b = new BaseContext('name goes here'));
        $this->assertSame($b, $v->getContext());
        $this->assertSame('name goes here', $v->getContext()->getName());
        $this->assertSame('name goes here', $v->getDisplayName());

        $v = new Value($b = new BaseContext(1234));
        $this->assertSame($b, $v->getContext());
        $this->assertSame(1234, $v->getContext()->getName());
        $this->assertSame('1234', $v->getDisplayName());
    }

    /**
     * @covers \Kint\Zval\Value::__clone
     */
    public function testClone()
    {
        $c = new BaseContext('base');
        $v = new Value($c);
        $this->assertSame($c, $v->getContext());

        $v2 = clone $v;
        $this->assertEquals($v->getContext(), $v2->getContext());
        $this->assertNotSame($v->getContext(), $v2->getContext());
    }

    /**
     * @covers \Kint\Zval\Value::getContext
     */
    public function testGetContext()
    {
        $c = new BaseContext('base');
        $v = new Value($c);
        $this->assertSame($c, $v->getContext());
    }

    /**
     * @covers \Kint\Zval\Value::addRepresentation
     * @covers \Kint\Zval\Value::getRepresentations
     */
    public function testAddRepresentation()
    {
        $o = new Value(new BaseContext('base'));

        $this->assertSame([], $o->getRepresentations());
        $this->assertNull($o->value);

        $this->assertTrue($o->addRepresentation($r1 = new Representation('Rep 1')));
        $this->assertSame(
            [
                'rep_1' => $r1,
            ],
            $o->getRepresentations()
        );
        $this->assertNull($o->value);

        $this->assertFalse($o->addRepresentation($r1));
        $this->assertSame(
            [
                'rep_1' => $r1,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r2 = new Representation('Rep 2')));
        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r3 = new Representation('Rep 3'), 0));
        $this->assertSame(
            [
                'rep_3' => $r3,
                'rep_1' => $r1,
                'rep_2' => $r2,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r4 = new Representation('Rep 4'), 1));
        $this->assertSame(
            [
                'rep_3' => $r3,
                'rep_4' => $r4,
                'rep_1' => $r1,
                'rep_2' => $r2,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r5 = new Representation('Rep 5'), 100));
        $this->assertSame(
            [
                'rep_3' => $r3,
                'rep_4' => $r4,
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_5' => $r5,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r6 = new Representation('Rep 6'), -100));
        $this->assertSame(
            [
                'rep_6' => $r6,
                'rep_3' => $r3,
                'rep_4' => $r4,
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_5' => $r5,
            ],
            $o->getRepresentations()
        );

        $this->assertNull($o->value);
    }

    /**
     * @covers \Kint\Zval\Value::replaceRepresentation
     */
    public function testReplaceRepresentation()
    {
        $o = new Value(new BaseContext('base'));
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation($r2 = new Representation('Rep 2'));
        $o->addRepresentation($r3 = new Representation('Rep 3'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->replaceRepresentation($r2_2 = new Representation('Rep 2'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2_2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->replaceRepresentation($r2, 0);

        $this->assertSame(
            [
                'rep_2' => $r2,
                'rep_1' => $r1,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->replaceRepresentation($r2_2, 1);

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2_2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );
    }

    /**
     * @covers \Kint\Zval\Value::removeRepresentation
     */
    public function testRemoveRepresentation()
    {
        $o = new Value(new BaseContext('base'));
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation($r2 = new Representation('Rep 2'));
        $o->addRepresentation($r3 = new Representation('Rep 3'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->removeRepresentation('rep_2');

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->removeRepresentation($r1);
        $this->assertSame(['rep_3' => $r3], $o->getRepresentations());
    }

    /**
     * @covers \Kint\Zval\Value::getRepresentation
     */
    public function testGetRepresentation()
    {
        $o = new Value(new BaseContext('base'));
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation($r2 = new Representation('Rep 2'));
        $o->addRepresentation($r3 = new Representation('Rep 3'));

        $this->assertSame($r1, $o->getRepresentation('rep_1'));
        $this->assertSame($r2, $o->getRepresentation('rep_2'));
        $this->assertSame($r3, $o->getRepresentation('rep_3'));
        $this->assertNull($o->getRepresentation('Non-existant representation name'));
    }

    /**
     * @covers \Kint\Zval\Value::getRepresentations
     */
    public function testGetRepresentations()
    {
        $o = new Value(new BaseContext('base'));
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation($r2 = new Representation('Rep 2'));
        $o->addRepresentation($r3 = new Representation('Rep 3'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );
    }

    /**
     * @covers \Kint\Zval\Value::clearRepresentations
     */
    public function testClearRepresentations()
    {
        $o = new Value(new BaseContext('base'));
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation(new Representation('Rep 2'));
        $o->addRepresentation(new Representation('Rep 3'));
        $o->value = $r1;

        $o->clearRepresentations();

        $this->assertSame([], $o->getRepresentations());
        $this->assertSame($r1, $o->value);
    }

    /**
     * @covers \Kint\Zval\Value::getType
     */
    public function testGetType()
    {
        $o = new Value(new BaseContext('base'));
        $o->type = 'array';
        $this->assertSame('array', $o->getType());
    }

    /**
     * @covers \Kint\Zval\Value::getSize
     */
    public function testGetSize()
    {
        $o = new Value(new BaseContext('base'));
        $this->assertNull($o->getSize());
        $o->size = 0;
        $this->assertSame('0', $o->getSize());
        $o->size = 42;
        $this->assertSame('42', $o->getSize());
    }

    /**
     * @covers \Kint\Zval\Value::getValueShort
     */
    public function testGetValueShort()
    {
        $o = new Value(new BaseContext('base'));
        $r = new Representation('contents');
        $this->assertNull($o->getValueShort());
        $o->value = $r;

        $r->contents = true;
        $this->assertNull($o->getValueShort());
        $o->type = 'boolean';
        $this->assertSame('true', $o->getValueShort());
        $r->contents = false;
        $this->assertSame('false', $o->getValueShort());
        $o->type = 'integer';
        $r->contents = 1234;
        $this->assertSame('1234', $o->getValueShort());
        $o->type = 'double';
        $r->contents = 1234.5678;
        $this->assertSame('1234.5678', $o->getValueShort());
        $o->type = 'array';
        $r->contents = [];
        $this->assertNull($o->getValueShort());
        $o->type = 'string';
        $r->contents = 'string';
        $this->assertNull($o->getValueShort());
    }

    /**
     * @covers \Kint\Zval\Value::getDisplayName
     */
    public function testGetDisplayName()
    {
        $o = new Value(new BaseContext('base'));
        $this->assertSame($o->getDisplayName(), $o->getContext()->getName());
    }

    /**
     * @covers \Kint\Zval\Value::transplant
     */
    public function testTransplant()
    {
        $o = new Value(new BaseContext('base'));
        $o->type = 'type';
        $o->size = 42;
        $o->hints = [
            'test' => true,
            'transplant' => true,
            'hints' => true,
        ];
        $o->value = new Representation('Test value');

        $r = new Representation('Test');
        $o->addRepresentation($r);

        $o2 = new Value(new BaseContext('base'));
        $o2->transplant($o);

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
        $this->assertSame($o->value, $o2->value);

        $o2 = new Value(new BaseContext('base'));
        $r2 = new Representation('Test 2');
        $o2->addRepresentation($r2);
        $o2->hints = [
            'test' => true,
            'thoroughly' => true,
        ];

        $o2->transplant($o);

        $this->assertSame(['test_2' => $r2, 'test' => $r], $o2->getRepresentations());
        $this->assertSame([
            'test' => true,
            'thoroughly' => true,
            'transplant' => true,
            'hints' => true,
        ], $o2->hints);
    }
}
