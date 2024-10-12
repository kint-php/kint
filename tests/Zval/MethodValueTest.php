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

use Exception;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Zval\Context\ClassDeclaredContext;
use Kint\Zval\Context\MethodContext;
use Kint\Zval\DeclaredCallableBag;
use Kint\Zval\MethodValue;
use Kint\Zval\Representation\CallableDefinitionRepresentation;
use LogicException;
use Random\Randomizer;
use ReflectionMethod;
use stdClass;
use TypeError;

/**
 * @coversNothing
 */
class MethodValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Zval\MethodValue::__construct
     * @covers \Kint\Zval\MethodValue::getContext
     */
    public function testConstruct()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $m = new MethodValue($reflection);
        $this->assertSame('mix', $m->getContext()->getName());
        $this->assertInstanceOf(DeclaredCallableBag::class, $m->getCallableBag());
        $this->assertFalse($m->getCallableBag()->internal);
        $this->assertInstanceOf(MethodContext::class, $m->getContext());
        $this->assertSame(TestClass::class, $m->getContext()->owner_class);
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $m->getContext()->access);
        $this->assertTrue($m->getContext()->static);
        $this->assertTrue($m->getContext()->final);
        $this->assertFalse($m->getContext()->abstract);
        $this->assertInstanceOf(CallableDefinitionRepresentation::class, $m->getRepresentation('callable_definition'));

        $reflection = new ReflectionMethod(TestClass::class, 'arrayHint');
        $m = new MethodValue($reflection);
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $m->getContext()->access);
        $this->assertInstanceOf(CallableDefinitionRepresentation::class, $m->getRepresentation('callable_definition'));

        $reflection = new ReflectionMethod(ReflectionMethod::class, '__construct');
        $m = new MethodValue($reflection);
        $this->assertTrue($m->getCallableBag()->internal);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $m->getContext()->access);
        $this->assertNull($m->getRepresentation('callable_definition'));
    }

    /**
     * @covers \Kint\Zval\MethodValue::__construct
     */
    public function testConstructWrongType()
    {
        $this->expectException(TypeError::class);

        $m = new MethodValue(new stdClass());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getContext
     */
    public function testGetContext()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $v = new MethodValue($reflection);
        $this->assertInstanceOf(MethodContext::class, $v->getContext());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getCallableBag
     */
    public function testGetCallableBag()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $v = new MethodValue($reflection);
        $this->assertInstanceOf(DeclaredCallableBag::class, $v->getCallableBag());
        $this->assertCount(4, $v->getCallableBag()->parameters);
    }

    /**
     * @covers \Kint\Zval\MethodValue::getDefinitionRepresentation
     */
    public function testGetDefinitionRepresentation()
    {
        $reflection = new ReflectionMethod(TestClass::class, '__construct');
        $v = new MethodValue($reflection);
        $this->assertInstanceOf(CallableDefinitionRepresentation::class, $v->getDefinitionRepresentation());
        $this->assertSame($reflection->getDocComment(), $v->getDefinitionRepresentation()->contents);
    }

    /**
     * @covers \Kint\Zval\MethodValue::getDisplayName
     */
    public function testGetDisplayName()
    {
        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'mix'));
        $this->assertSame(
            'mix(array &$x, ?Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\')',
            $m->getDisplayName()
        );
    }

    /**
     * @covers \Kint\Zval\MethodValue::getDisplayValue
     */
    public function testGetDisplayValue()
    {
        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__construct'));
        $this->assertSame(
            'This is a constructor for a TestClass with the first line of the docstring split into two different lines.',
            $m->getDisplayValue()
        );

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'arrayHint'));
        $this->assertNull($m->getDisplayValue());

        $m = new MethodValue(new ReflectionMethod(ReflectionMethod::class, '__construct'));
        $this->assertNull($m->getDisplayValue());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrl()
    {
        $m = new MethodValue(new ReflectionMethod(Exception::class, '__construct'));
        $this->assertSame(
            'https://www.php.net/exception.construct',
            $m->getPhpDocUrl()
        );
    }

    /**
     * @covers \Kint\Zval\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrlParent()
    {
        $m = new MethodValue(new ReflectionMethod(LogicException::class, 'getMessage'));
        $this->assertSame(
            'https://www.php.net/exception.getmessage',
            $m->getPhpDocUrl()
        );
    }

    /**
     * @covers \Kint\Zval\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrlUserDefined()
    {
        $m = new MethodValue(new ReflectionMethod(__CLASS__, __FUNCTION__));
        $this->assertNull($m->getPhpDocUrl());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrlNamespaced()
    {
        if (!KINT_PHP82) {
            $this->markTestSkipped('Not testing namespaced PHP doc pages below PHP 8.2');
        }

        $m = new MethodValue(new ReflectionMethod(Randomizer::class, 'getBytes'));
        $this->assertSame(
            'https://www.php.net/random-randomizer.getbytes',
            $m->getPhpDocUrl()
        );
    }
}
