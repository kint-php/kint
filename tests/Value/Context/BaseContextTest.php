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

namespace Kint\Test\Value\Context;

use InvalidArgumentException;
use Kint\Test\KintTestCase;
use Kint\Value\Context\BaseContext;

/**
 * @coversNothing
 */
class BaseContextTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\Context\BaseContext::__construct
     */
    public function testConstruct()
    {
        $c = new BaseContext('name goes here');
        $this->assertSame('name goes here', $c->name);

        $this->expectException(InvalidArgumentException::class);
        new BaseContext($c);
    }

    /**
     * @covers \Kint\Value\Context\BaseContext::getName
     */
    public function testGetName()
    {
        $c = new BaseContext('$var');
        $this->assertSame('$var', $c->getName());
        $c->name = '($a + $b)';
        $this->assertSame('($a + $b)', $c->getName());
        $c->name = 'This is just a name, nothing more, nothing less.';
        $this->assertSame('This is just a name, nothing more, nothing less.', $c->getName());
    }

    /**
     * @covers \Kint\Value\Context\BaseContext::getDepth
     */
    public function testGetDepth()
    {
        $c = new BaseContext('base');
        $this->assertSame(0, $c->getDepth());
        $c->depth = 42;
        $this->assertSame(42, $c->getDepth());
    }

    /**
     * @covers \Kint\Value\Context\BaseContext::getOperator
     */
    public function testGetOperator()
    {
        $c = new BaseContext('base');
        $this->assertNull($c->getOperator());
    }

    /**
     * @covers \Kint\Value\Context\BaseContext::isRef
     */
    public function testIsRef()
    {
        $c = new BaseContext('base');
        $this->assertFalse($c->isRef());
        $c->reference = true;
        $this->assertTrue($c->isRef());
    }

    /**
     * @covers \Kint\Value\Context\BaseContext::isAccessible
     */
    public function testIsAccessible()
    {
        $c = new BaseContext('base');
        $this->assertTrue($c->isAccessible(null));
        $this->assertTrue($c->isAccessible(__CLASS__));
    }

    /**
     * @covers \Kint\Value\Context\BaseContext::getAccessPath
     */
    public function testGetAccessPath()
    {
        $c = new BaseContext('base');
        $this->assertNull($c->getAccessPath());
        $c->access_path = 'abcdefg, hijk elemeno p';
        $this->assertSame('abcdefg, hijk elemeno p', $c->getAccessPath());
    }
}
