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

namespace Kint\Test\Zval\Context;

use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\Context\ClassDeclaredContext;
use Kint\Zval\Context\MethodContext;
use Kint\Zval\InstanceValue;
use Kint\Zval\MethodValue;
use ReflectionMethod;

/**
 * @coversNothing
 */
class MethodContextTest extends KintTestCase
{
    /**
     * @covers \Kint\Zval\Context\MethodContext::setAccessPathFromParent
     */
    public function testSetAccessPathFromParent()
    {
        $b = new BaseContext('$tc');
        $b->access_path = '$tc';
        $o = new InstanceValue($b, TestClass::class, 'objhash', 314159);

        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__construct'));
        $m = $m->getContext();
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('new \\Kint\\Test\\Fixtures\\TestClass()', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'staticMethod'));
        $m = $m->getContext();
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('\\Kint\\Test\\Fixtures\\TestClass::staticMethod()', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'finalMethod'));
        $m = $m->getContext();
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('$tc->finalMethod()', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'mix'));
        $m = $m->getContext();
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame(
            '\\Kint\\Test\\Fixtures\\TestClass::mix()',
            $m->getAccessPath()
        );

        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__clone'));
        $m = $m->getContext();
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('clone $tc', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__invoke'));
        $m = $m->getContext();
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('$tc()', $m->getAccessPath());

        // Tests both tostring and case insensitivity
        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__tostring'));
        $m = $m->getContext();
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('__ToStRiNg', $m->name);
        $this->assertSame('(string) $tc', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__get'));
        $m = $m->getContext();
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertNull($m->getAccessPath());

        $b->access_path = null;
        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'finalMethod'));
        $m = $m->getContext();
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertNull($m->getAccessPath());
    }

    /**
     * @covers \Kint\Zval\Context\MethodContext::getModifiers
     */
    public function testGetModifiers()
    {
        $c = new MethodContext('staticMethod', 'class', ClassDeclaredContext::ACCESS_PRIVATE);
        $c->static = true;
        $this->assertSame('private static', $c->getModifiers());

        $c = new MethodContext('finalMethod', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $c->final = true;
        $this->assertSame('final public', $c->getModifiers());

        $c = new MethodContext('abstractMethod', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $c->abstract = true;
        $this->assertSame('abstract public', $c->getModifiers());

        $c = new MethodContext('mix', 'class', ClassDeclaredContext::ACCESS_PROTECTED);
        $c->final = true;
        $c->static = true;
        $this->assertSame('final protected static', $c->getModifiers());
    }

    /**
     * @covers \Kint\Zval\Context\MethodContext::getOperator
     */
    public function testGetOperator()
    {
        $c = new MethodContext('method', 'class', ClassDeclaredContext::ACCESS_PUBLIC);

        $this->assertSame('->', $c->getOperator());

        $c->static = true;

        $this->assertSame('::', $c->getOperator());
    }
}
