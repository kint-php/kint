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

use DateTime;
use Exception;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\MethodContext;
use Kint\Value\DeclaredCallableBag;
use Kint\Value\MethodValue;
use Kint\Value\Representation\CallableDefinitionRepresentation;
use LogicException;
use Random\Randomizer;
use ReflectionMethod;

/**
 * @coversNothing
 */
class MethodValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\MethodValue::__construct
     * @covers \Kint\Value\MethodValue::getContext
     */
    public function testConstruct()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
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
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $m->getContext()->access);
        $this->assertInstanceOf(CallableDefinitionRepresentation::class, $m->getRepresentation('callable_definition'));

        $reflection = new ReflectionMethod(ReflectionMethod::class, '__construct');
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertTrue($m->getCallableBag()->internal);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $m->getContext()->access);
        $this->assertNull($m->getRepresentation('callable_definition'));
    }

    /**
     * @covers \Kint\Value\MethodValue::getHint
     */
    public function testGetHint()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $v = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));

        $this->assertSame('callable', $v->getHint());

        $v->flags |= AbstractValue::FLAG_BLACKLIST;

        $this->assertSame('blacklist', $v->getHint());
    }

    /**
     * @covers \Kint\Value\MethodValue::getContext
     */
    public function testGetContext()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $v = new MethodValue($c = new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertSame($c, $v->getContext());
    }

    /**
     * @covers \Kint\Value\MethodValue::getCallableBag
     */
    public function testGetCallableBag()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $v = new MethodValue(new MethodContext($reflection), $b = new DeclaredCallableBag($reflection));
        $this->assertSame($b, $v->getCallableBag());
        $this->assertCount(4, $v->getCallableBag()->parameters);
    }

    /**
     * @covers \Kint\Value\MethodValue::getDefinitionRepresentation
     */
    public function testGetDefinitionRepresentation()
    {
        $reflection = new ReflectionMethod(TestClass::class, '__construct');
        $v = new MethodValue(new MethodContext($reflection), $b = new DeclaredCallableBag($reflection));
        $this->assertInstanceOf(CallableDefinitionRepresentation::class, $v->getDefinitionRepresentation());
        $this->assertSame($b->docstring, $v->getDefinitionRepresentation()->getDocstring());
    }

    /**
     * @covers \Kint\Value\MethodValue::getDisplayName
     */
    public function testGetDisplayName()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $v = new MethodValue($c = new MethodContext($reflection), $b = new DeclaredCallableBag($reflection));
        $this->assertSame(
            TestClass::class.'::mix(array &$x, ?Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\')',
            $v->getDisplayName()
        );

        $c->inherited = true;
        $c->access = ClassDeclaredContext::ACCESS_PRIVATE;
        $b->parameters = [];

        $v = new MethodValue($c, $b);
        $this->assertSame(TestClass::class.'::mix()', $v->getDisplayName());

        $reflection = new ReflectionMethod(TestClass::class, 'normalMethod');
        $v = new MethodValue($c = new MethodContext($reflection), $b = new DeclaredCallableBag($reflection));
        $b->parameters = [];

        $this->assertSame('normalMethod()', $v->getDisplayName());

        $c->inherited = true;
        $c->access = ClassDeclaredContext::ACCESS_PRIVATE;

        $this->assertSame(TestClass::class.'::normalMethod()', $v->getDisplayName());
    }

    /**
     * @covers \Kint\Value\MethodValue::getFullyQualifiedDisplayName
     */
    public function testGetFullyQualifiedDisplayName()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'normalMethod');
        $v = new MethodValue($c = new MethodContext($reflection), $b = new DeclaredCallableBag($reflection));
        $b->parameters = [];
        $this->assertNotSame($v->getDisplayName(), $v->getFullyQualifiedDisplayName());
        $this->assertSame(TestClass::class.'::normalMethod()', $v->getFullyQualifiedDisplayName());
    }

    /**
     * @covers \Kint\Value\MethodValue::getDisplayValue
     */
    public function testGetDisplayValue()
    {
        $reflection = new ReflectionMethod(TestClass::class, '__construct');
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertSame(
            'This is a constructor for a TestClass with the first line of the docstring split into two different lines.',
            $m->getDisplayValue()
        );

        $reflection = new ReflectionMethod(TestClass::class, 'arrayHint');
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertNull($m->getDisplayValue());

        $reflection = new ReflectionMethod(ReflectionMethod::class, '__construct');
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertNull($m->getDisplayValue());
    }

    /**
     * @covers \Kint\Value\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrl()
    {
        $reflection = new ReflectionMethod(Exception::class, '__construct');
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertSame(
            'https://www.php.net/exception.construct',
            $m->getPhpDocUrl()
        );
    }

    /**
     * @covers \Kint\Value\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrlParent()
    {
        $reflection = new ReflectionMethod(LogicException::class, 'getMessage');
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertSame(
            'https://www.php.net/exception.getmessage',
            $m->getPhpDocUrl()
        );
    }

    /**
     * @covers \Kint\Value\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrlStatic()
    {
        $reflection = new ReflectionMethod(DateTime::class, 'createFromFormat');
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertSame(
            'https://www.php.net/datetime.createfromformat',
            $m->getPhpDocUrl()
        );
    }

    /**
     * @covers \Kint\Value\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrlUserDefined()
    {
        $reflection = new ReflectionMethod(__CLASS__, __FUNCTION__);
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertNull($m->getPhpDocUrl());
    }

    /**
     * @covers \Kint\Value\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrlNamespaced()
    {
        if (!KINT_PHP82) {
            $this->markTestSkipped('Not testing namespaced PHP doc pages below PHP 8.2');
        }

        $reflection = new ReflectionMethod(Randomizer::class, 'getBytes');
        $m = new MethodValue(new MethodContext($reflection), new DeclaredCallableBag($reflection));
        $this->assertSame(
            'https://www.php.net/random-randomizer.getbytes',
            $m->getPhpDocUrl()
        );
    }
}
