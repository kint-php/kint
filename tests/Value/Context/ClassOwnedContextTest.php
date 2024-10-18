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

use __PHP_Incomplete_Class;
use Kint\Test\KintTestCase;
use Kint\Value\Context\ClassOwnedContext;

/**
 * @coversNothing
 */
class ClassOwnedContextTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\Context\ClassOwnedContext::__construct
     */
    public function testConstruct()
    {
        $c = new ClassOwnedContext('name', 'class');
        $this->assertSame('class', $c->owner_class);
    }

    /**
     * @covers \Kint\Value\Context\ClassOwnedContext::getName
     */
    public function testGetName()
    {
        $c = new ClassOwnedContext('name', 'class');
        $this->assertSame('name', $c->getName());
        $c->name = 123;
        $this->assertNotSame(123, $c->getName());
        $this->assertSame('123', $c->getName());
    }

    /**
     * @covers \Kint\Value\Context\ClassOwnedContext::getOperator
     */
    public function testGetOperator()
    {
        $c = new ClassOwnedContext('name', 'class');
        $this->assertSame('->', $c->getOperator());
    }

    /**
     * @covers \Kint\Value\Context\ClassOwnedContext::isAccessible
     */
    public function testIsAccessible()
    {
        $c = new ClassOwnedContext('name', __PHP_Incomplete_Class::class);
        $this->assertFalse($c->isAccessible(null));
        $this->assertFalse($c->isAccessible(__PHP_Incomplete_Class::class));
        $c->owner_class = 'asdf';
        $this->assertTrue($c->isAccessible(null));
    }
}
