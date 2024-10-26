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

namespace Kint\Test\Value;

use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\StringRepresentation;
use OutOfRangeException;

/**
 * @coversNothing
 */
class AbstractValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\AbstractValue::__construct
     * @covers \Kint\Value\AbstractValue::getContext
     */
    public function testConstruct()
    {
        $c = new BaseContext('name goes here');
        $v = $this->getMockForAbstractClass(AbstractValue::class, [$c, 'testtype']);
        $this->assertSame($c, $v->getContext());
        $this->assertSame('name goes here', $v->getContext()->getName());
        $this->assertSame('name goes here', $v->getDisplayName());

        $c = new BaseContext(1234);
        $v = $this->getMockForAbstractClass(AbstractValue::class, [$c, 'testtype']);
        $this->assertSame($c, $v->getContext());
        $this->assertSame(1234, $v->getContext()->getName());
        $this->assertSame('1234', $v->getDisplayName());
    }

    /**
     * @covers \Kint\Value\AbstractValue::__clone
     */
    public function testClone()
    {
        $c = new BaseContext('base');
        $v = $this->getMockForAbstractClass(AbstractValue::class, [$c, 'testtype']);
        $this->assertSame($c, $v->getContext());

        $v2 = clone $v;
        $this->assertEquals($v->getContext(), $v2->getContext());
        $this->assertNotSame($v->getContext(), $v2->getContext());
    }

    /**
     * @covers \Kint\Value\AbstractValue::getContext
     */
    public function testGetContext()
    {
        $c = new BaseContext('base');
        $v = $this->getMockForAbstractClass(AbstractValue::class, [$c, 'testtype']);
        $this->assertSame($c, $v->getContext());
    }

    /**
     * @covers \Kint\Value\AbstractValue::getHint
     */
    public function testGetHint()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $this->assertNull($v->getHint());

        $v->flags |= AbstractValue::FLAG_ARRAY_LIMIT;
        $this->assertSame('array_limit', $v->getHint());

        $v->flags |= AbstractValue::FLAG_DEPTH_LIMIT;
        $this->assertSame('depth_limit', $v->getHint());

        $v->flags |= AbstractValue::FLAG_RECURSION;
        $this->assertSame('recursion', $v->getHint());

        $v->flags |= AbstractValue::FLAG_BLACKLIST;
        $this->assertSame('blacklist', $v->getHint());

        $v->flags = 1024;
        $this->assertNull($v->getHint());
    }

    /**
     * @covers \Kint\Value\AbstractValue::addRepresentation
     * @covers \Kint\Value\AbstractValue::getRepresentations
     */
    public function testAddRepresentation()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);

        $this->assertSame([], $v->getRepresentations());

        $v->addRepresentation($r1 = new StringRepresentation('Rep 1', 'contents'));
        $this->assertSame(
            [
                'rep_1' => $r1,
            ],
            $v->getRepresentations()
        );

        $caught = false;
        try {
            $v->addRepresentation(new StringRepresentation('Rep 1', 'other contents'));
        } catch (OutOfRangeException $e) {
            $caught = true;
        }
        $this->assertTrue($caught);
        $this->assertSame(
            [
                'rep_1' => $r1,
            ],
            $v->getRepresentations()
        );
        $this->assertSame('contents', $v->getRepresentation('rep_1')->getValue());

        $v->addRepresentation($r2 = new StringRepresentation('Rep 2', 'contents'));
        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
            ],
            $v->getRepresentations()
        );

        $v->addRepresentation($r3 = new StringRepresentation('Rep 3', 'contents'), 0);
        $this->assertSame(
            [
                'rep_3' => $r3,
                'rep_1' => $r1,
                'rep_2' => $r2,
            ],
            $v->getRepresentations()
        );

        $v->addRepresentation($r4 = new StringRepresentation('Rep 4', 'contents'), 1);
        $this->assertSame(
            [
                'rep_3' => $r3,
                'rep_4' => $r4,
                'rep_1' => $r1,
                'rep_2' => $r2,
            ],
            $v->getRepresentations()
        );

        $v->addRepresentation($r5 = new StringRepresentation('Rep 5', 'contents'), 100);
        $this->assertSame(
            [
                'rep_3' => $r3,
                'rep_4' => $r4,
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_5' => $r5,
            ],
            $v->getRepresentations()
        );

        $v->addRepresentation($r6 = new StringRepresentation('Rep 6', 'contents'), -100);
        $this->assertSame(
            [
                'rep_6' => $r6,
                'rep_3' => $r3,
                'rep_4' => $r4,
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_5' => $r5,
            ],
            $v->getRepresentations()
        );
    }

    /**
     * @covers \Kint\Value\AbstractValue::replaceRepresentation
     */
    public function testReplaceRepresentation()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $v->addRepresentation($r1 = new StringRepresentation('Rep 1', 'contents'));
        $v->addRepresentation($r2 = new StringRepresentation('Rep 2', 'contents'));
        $v->addRepresentation($r3 = new StringRepresentation('Rep 3', 'contents'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_3' => $r3,
            ],
            $v->getRepresentations()
        );

        $v->replaceRepresentation($r2_2 = new StringRepresentation('Rep 2', 'contents'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2_2,
                'rep_3' => $r3,
            ],
            $v->getRepresentations()
        );

        $v->replaceRepresentation($r2, 0);

        $this->assertSame(
            [
                'rep_2' => $r2,
                'rep_1' => $r1,
                'rep_3' => $r3,
            ],
            $v->getRepresentations()
        );

        $v->replaceRepresentation($r2_2, 1);

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2_2,
                'rep_3' => $r3,
            ],
            $v->getRepresentations()
        );
    }

    /**
     * @covers \Kint\Value\AbstractValue::removeRepresentation
     */
    public function testRemoveRepresentation()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $v->addRepresentation($r1 = new StringRepresentation('Rep 1', 'contents'));
        $v->addRepresentation($r2 = new StringRepresentation('Rep 2', 'contents'));
        $v->addRepresentation($r3 = new StringRepresentation('Rep 3', 'contents'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_3' => $r3,
            ],
            $v->getRepresentations()
        );

        $v->removeRepresentation('rep_2');

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_3' => $r3,
            ],
            $v->getRepresentations()
        );

        $v->removeRepresentation($r1);
        $this->assertSame(['rep_3' => $r3], $v->getRepresentations());
    }

    /**
     * @covers \Kint\Value\AbstractValue::getRepresentation
     */
    public function testGetRepresentation()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $v->addRepresentation($r1 = new StringRepresentation('Rep 1', 'contents'));
        $v->addRepresentation($r2 = new StringRepresentation('Rep 2', 'contents'));
        $v->addRepresentation($r3 = new StringRepresentation('Rep 3', 'contents'));

        $this->assertSame($r1, $v->getRepresentation('rep_1'));
        $this->assertSame($r2, $v->getRepresentation('rep_2'));
        $this->assertSame($r3, $v->getRepresentation('rep_3'));
        $this->assertNull($v->getRepresentation('Non-existant representation name'));
    }

    /**
     * @covers \Kint\Value\AbstractValue::getRepresentations
     */
    public function testGetRepresentations()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $v->addRepresentation($r1 = new StringRepresentation('Rep 1', 'contents'));
        $v->addRepresentation($r2 = new StringRepresentation('Rep 2', 'contents'));
        $v->addRepresentation($r3 = new StringRepresentation('Rep 3', 'contents'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_3' => $r3,
            ],
            $v->getRepresentations()
        );
    }

    /**
     * @covers \Kint\Value\AbstractValue::appendRepresentations
     */
    public function testAppendRepresentations()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $r1 = new StringRepresentation('rep 1', 'contents');
        $v->addRepresentation($r1);
        $r2 = new StringRepresentation('rep 2', 'contents');
        $v->addRepresentation($r2);

        $v2 = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $r3 = new StringRepresentation('rep 3', 'contents');
        $v2->addRepresentation($r3);

        $this->assertSame([$r3], \array_values($v2->getRepresentations()));

        $v2->appendRepresentations($v->getRepresentations());

        $this->assertSame([$r3, $r1, $r2], \array_values($v2->getRepresentations()));
    }

    /**
     * @covers \Kint\Value\AbstractValue::clearRepresentations
     */
    public function testClearRepresentations()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $v->addRepresentation(new StringRepresentation('Rep 1', 'contents'));
        $v->addRepresentation(new StringRepresentation('Rep 2', 'contents'));
        $v->addRepresentation(new StringRepresentation('Rep 3', 'contents'));

        $this->assertCount(3, $v->getRepresentations());
        $v->clearRepresentations();
        $this->assertSame([], $v->getRepresentations());
    }

    /**
     * @covers \Kint\Value\AbstractValue::getType
     */
    public function testGetType()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $this->assertSame('testtype', $v->getType());
    }

    /**
     * @covers \Kint\Value\AbstractValue::getDisplayType
     */
    public function testGetDisplayType()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $this->assertSame('testtype', $v->getDisplayType());
    }

    /**
     * @covers \Kint\Value\AbstractValue::getDisplaySize
     */
    public function testGetDisplaySize()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $this->assertNull($v->getDisplaySize());
    }

    /**
     * @covers \Kint\Value\AbstractValue::getDisplayValue
     */
    public function testGetDisplayValue()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $this->assertNull($v->getDisplayValue());
    }

    /**
     * @covers \Kint\Value\AbstractValue::getDisplayName
     */
    public function testGetDisplayName()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $this->assertSame($v->getContext()->getName(), $v->getDisplayName());
    }

    /**
     * @covers \Kint\Value\AbstractValue::getDisplayChildren
     */
    public function testGetDisplayChildren()
    {
        $v = $this->getMockForAbstractClass(AbstractValue::class, [new BaseContext('base'), 'testtype']);
        $this->assertSame([], $v->getDisplayChildren());
    }
}
