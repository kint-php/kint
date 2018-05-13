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

namespace Kint\Test\Object;

use Kint\Object\BasicObject;
use Kint\Object\InstanceObject;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\Fixtures\TestClass;
use PHPUnit\Framework\TestCase;
use stdClass;

class InstanceObjectTest extends TestCase
{
    /**
     * @covers \Kint\Object\InstanceObject::sortByHierarchy
     */
    public function testSortByHierarchy()
    {
        $this->assertSame(1, InstanceObject::sortByHierarchy('Kint\\Test\\Fixtures\\TestClass', 'Kint\\Test\\Fixtures\\ChildTestClass'));
        $this->assertSame(-1, InstanceObject::sortByHierarchy('Kint\\Test\\Fixtures\\ChildTestClass', 'Kint\\Test\\Fixtures\\TestClass'));
        $this->assertSame(0, InstanceObject::sortByHierarchy('Kint\\Test\\Fixtures\\TestClass', 'Kint\\Test\\Fixtures\\TestClass'));
        $this->assertSame(0, InstanceObject::sortByHierarchy(new stdClass(), 'Kint\\Test\\Fixtures\\TestClass'));

        $p = new Parser();
        $b = BasicObject::blank();

        $tc = new TestClass();
        $ctc = new ChildTestClass();

        $tc = $p->parse($tc, clone $b);
        $ctc = $p->parse($ctc, clone $b);

        $this->assertSame(1, InstanceObject::sortByHierarchy($tc, $ctc));
        $this->assertSame(-1, InstanceObject::sortByHierarchy($ctc, $tc));
        $this->assertSame(0, InstanceObject::sortByHierarchy($tc, $tc));
        $this->assertSame(0, InstanceObject::sortByHierarchy($tc, $b));
    }

    /**
     * @covers \Kint\Object\InstanceObject::transplant
     */
    public function testTransplant()
    {
        $p = new Parser();
        $b = BasicObject::blank();
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $o2 = new InstanceObject();
        $o2->transplant($o);

        $o->hints[] = 'object';

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
    }

    /**
     * @covers \Kint\Object\InstanceObject::getType
     */
    public function testGetType()
    {
        $p = new Parser();
        $b = BasicObject::blank();
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertSame('Kint\\Test\\Fixtures\\ChildTestClass', $o->getType());
    }
}
