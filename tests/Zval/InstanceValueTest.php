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

use Kint\Parser\Parser;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Zval\InstanceValue;
use Kint\Zval\Value;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @coversNothing
 */
class InstanceValueTest extends TestCase
{
    /**
     * @covers \Kint\Zval\InstanceValue::sortByHierarchy
     */
    public function testSortByHierarchy()
    {
        $this->assertSame(1, InstanceValue::sortByHierarchy(TestClass::class, ChildTestClass::class));
        $this->assertSame(-1, InstanceValue::sortByHierarchy(ChildTestClass::class, TestClass::class));
        $this->assertSame(0, InstanceValue::sortByHierarchy(TestClass::class, TestClass::class));
        $this->assertSame(0, InstanceValue::sortByHierarchy(stdClass::class, TestClass::class));
    }

    /**
     * @covers \Kint\Zval\InstanceValue::transplant
     */
    public function testTransplant()
    {
        $p = new Parser();
        $b = Value::blank();
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $o2 = new InstanceValue();
        $o2->transplant($o);

        $o->hints[] = 'object';

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
    }

    /**
     * @covers \Kint\Zval\InstanceValue::getType
     */
    public function testGetType()
    {
        $p = new Parser();
        $b = Value::blank();
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertSame('Kint\\Test\\Fixtures\\ChildTestClass', $o->getType());
    }
}
