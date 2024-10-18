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
use Kint\Value\Context\BaseContext;
use Kint\Value\InstanceValue;

/**
 * @coversNothing
 */
class InstanceValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\InstanceValue::__construct
     */
    public function testConstruct()
    {
        $c = new BaseContext('name');
        $v = new InstanceValue($c, 'classname', 'hash', 1234);

        $this->assertSame($c, $v->getContext());
        $this->assertSame('classname', $v->getClassName());
        $this->assertSame('hash', $v->getSplObjectHash());
        $this->assertSame(1234, $v->getSplObjectId());
    }

    /**
     * @covers \Kint\Value\InstanceValue::getClassName
     */
    public function testGetClassName()
    {
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'hash', 1234);
        $this->assertSame('classname', $v->getClassName());
        $v = new InstanceValue(new BaseContext('name'), 'other_class_name', 'hash', 1234);
        $this->assertSame('other_class_name', $v->getClassName());
    }

    /**
     * @covers \Kint\Value\InstanceValue::getSplObjectHash
     */
    public function testGetSplObjectHash()
    {
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'hash', 1234);
        $this->assertSame('hash', $v->getSplObjectHash());
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'otherhash', 1234);
        $this->assertSame('otherhash', $v->getSplObjectHash());
    }

    /**
     * @covers \Kint\Value\InstanceValue::getSplObjectId
     */
    public function testGetSplObjectId()
    {
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'hash', 1234);
        $this->assertSame(1234, $v->getSplObjectId());
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'otherhash', 5678);
        $this->assertSame(5678, $v->getSplObjectId());
    }

    /**
     * @covers \Kint\Value\InstanceValue::setChildren
     */
    public function testSetChildren()
    {
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'hash', 1234);
        $child = clone $v;
        $child2 = clone $child;

        $this->assertNull($v->getChildren());

        $v->setChildren([$child]);

        $this->assertEquals([$child2], $v->getChildren());
        $this->assertNotSame([$child2], $v->getChildren());
        $this->assertSame([$child], $v->getChildren());

        $v->setChildren(null);

        $this->assertNull($v->getChildren());
    }

    /**
     * @covers \Kint\Value\InstanceValue::getChildren
     */
    public function testGetChildren()
    {
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'hash', 1234);
        $child = clone $v;
        $child2 = clone $child;

        $v->setChildren([$child]);

        $this->assertEquals([$child2], $v->getChildren());
        $this->assertNotSame([$child2], $v->getChildren());
        $this->assertSame([$child], $v->getChildren());
    }

    /**
     * @covers \Kint\Value\InstanceValue::getDisplayType
     */
    public function testGetDisplayType()
    {
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'hash', 1234);

        $this->assertSame('classname', $v->getDisplayType());
    }

    /**
     * @covers \Kint\Value\InstanceValue::getDisplaySize
     */
    public function testGetDisplaySize()
    {
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'hash', 1234);
        $child = clone $v;

        $this->assertNull($v->getDisplaySize());

        $v->setChildren([]);
        $this->assertSame('0', $v->getDisplaySize());

        $v->setChildren([$child]);
        $this->assertSame('1', $v->getDisplaySize());

        $v->setChildren([$child, $child, $child]);
        $this->assertSame('3', $v->getDisplaySize());
    }

    /**
     * @covers \Kint\Value\InstanceValue::getDisplayChildren
     */
    public function testGetDisplayChildren()
    {
        $v = new InstanceValue(new BaseContext('name'), 'classname', 'hash', 1234);
        $child = clone $v;

        $this->assertNull($v->getChildren());
        $this->assertSame([], $v->getDisplayChildren());

        $v->setChildren([$child, $child]);

        $this->assertSame([$child, $child], $v->getChildren());
        $this->assertSame([$child, $child], $v->getDisplayChildren());
    }
}
