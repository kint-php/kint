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

use Kint\Parser\AbstractPlugin;
use Kint\Parser\PluginInterface;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\MethodContext;
use Kint\Value\InstanceValue;
use ReflectionMethod;

/**
 * @coversNothing
 */
class MethodContextTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\Context\MethodContext::__construct
     */
    public function testConstruct()
    {
        $r = new ReflectionMethod(AbstractPlugin::class, 'getTypes');
        $m = new MethodContext($r);
        $this->assertSame('getTypes', $m->name);
        $this->assertSame(PluginInterface::class, $m->owner_class);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $m->access);
        $this->assertSame(1, $m->depth);
        $this->assertFalse($m->static);
        $this->assertTrue($m->abstract);
        $this->assertFalse($m->final);
        $this->assertFalse($m->inherited);

        $r = new ReflectionMethod(TestClass::class, 'staticMethod');
        $m = new MethodContext($r);
        $this->assertSame('staticMethod', $m->name);
        $this->assertSame(TestClass::class, $m->owner_class);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $m->access);
        $this->assertTrue($m->static);
        $this->assertFalse($m->abstract);
        $this->assertFalse($m->final);
        $this->assertFalse($m->inherited);

        $r = new ReflectionMethod(TestClass::class, 'arrayHint');
        $m = new MethodContext($r);
        $this->assertSame('arrayHint', $m->name);
        $this->assertSame(TestClass::class, $m->owner_class);
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $m->access);
        $this->assertFalse($m->static);
        $this->assertFalse($m->abstract);
        $this->assertFalse($m->final);
        $this->assertFalse($m->inherited);

        $r = new ReflectionMethod(TestClass::class, 'finalMethod');
        $m = new MethodContext($r);
        $this->assertSame('finalMethod', $m->name);
        $this->assertSame(TestClass::class, $m->owner_class);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $m->access);
        $this->assertFalse($m->static);
        $this->assertFalse($m->abstract);
        $this->assertTrue($m->final);
        $this->assertFalse($m->inherited);

        $r = new ReflectionMethod(TestClass::class, 'mix');
        $m = new MethodContext($r);
        $this->assertSame('mix', $m->name);
        $this->assertSame(TestClass::class, $m->owner_class);
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $m->access);
        $this->assertTrue($m->static);
        $this->assertFalse($m->abstract);
        $this->assertTrue($m->final);
        $this->assertFalse($m->inherited);
    }

    /**
     * @covers \Kint\Value\Context\MethodContext::setAccessPathFromParent
     */
    public function testSetAccessPathFromParent()
    {
        $b = new BaseContext('$tc');
        $b->access_path = '$tc';
        $o = new InstanceValue($b, TestClass::class, 'objhash', 314159);

        $reflection = new ReflectionMethod(TestClass::class, '__construct');
        $m = new MethodContext($reflection);
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('new \\Kint\\Test\\Fixtures\\TestClass()', $m->getAccessPath());

        $m->setAccessPathFromParent(null);
        $this->assertNull($m->getAccessPath());

        $reflection = new ReflectionMethod(TestClass::class, 'staticMethod');
        $m = new MethodContext($reflection);
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('\\Kint\\Test\\Fixtures\\TestClass::staticMethod()', $m->getAccessPath());

        $m->setAccessPathFromParent(null);
        $this->assertSame('\\Kint\\Test\\Fixtures\\TestClass::staticMethod()', $m->getAccessPath());

        $reflection = new ReflectionMethod(TestClass::class, 'finalMethod');
        $m = new MethodContext($reflection);
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('$tc->finalMethod()', $m->getAccessPath());

        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $m = new MethodContext($reflection);
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame(
            '\\Kint\\Test\\Fixtures\\TestClass::mix()',
            $m->getAccessPath()
        );

        $reflection = new ReflectionMethod(TestClass::class, '__clone');
        $m = new MethodContext($reflection);
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('clone $tc', $m->getAccessPath());

        $reflection = new ReflectionMethod(TestClass::class, '__invoke');
        $m = new MethodContext($reflection);
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('$tc()', $m->getAccessPath());

        // Tests both tostring and case insensitivity
        $reflection = new ReflectionMethod(TestClass::class, '__tostring');
        $m = new MethodContext($reflection);
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertSame('__ToStRiNg', $m->name);
        $this->assertSame('(string) $tc', $m->getAccessPath());

        $reflection = new ReflectionMethod(TestClass::class, '__get');
        $m = new MethodContext($reflection);
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertNull($m->getAccessPath());

        $b->access_path = null;
        $reflection = new ReflectionMethod(TestClass::class, 'finalMethod');
        $m = new MethodContext($reflection);
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFromParent($o);
        $this->assertNull($m->getAccessPath());
    }

    /**
     * @covers \Kint\Value\Context\MethodContext::getModifiers
     */
    public function testGetModifiers()
    {
        $reflection = new ReflectionMethod(__CLASS__, __FUNCTION__);
        $c = new MethodContext($reflection);
        $c->access = ClassDeclaredContext::ACCESS_PRIVATE;
        $c->static = true;
        $this->assertSame('private static', $c->getModifiers());

        $c = new MethodContext($reflection);
        $c->final = true;
        $this->assertSame('final public', $c->getModifiers());

        $c = new MethodContext($reflection);
        $c->abstract = true;
        $this->assertSame('abstract public', $c->getModifiers());

        $c = new MethodContext($reflection);
        $c->access = ClassDeclaredContext::ACCESS_PROTECTED;
        $c->final = true;
        $c->static = true;
        $this->assertSame('final protected static', $c->getModifiers());
    }

    /**
     * @covers \Kint\Value\Context\MethodContext::getOperator
     */
    public function testGetOperator()
    {
        $c = new MethodContext(new ReflectionMethod(__CLASS__, __FUNCTION__));

        $this->assertSame('->', $c->getOperator());

        $c->static = true;

        $this->assertSame('::', $c->getOperator());
    }
}
