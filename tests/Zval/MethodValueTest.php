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
use Iterator;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\Fixtures\Php71TestClass;
use Kint\Test\Fixtures\Php7TestClass;
use Kint\Test\Fixtures\Php8TestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Zval\InstanceValue;
use Kint\Zval\MethodValue;
use Kint\Zval\Value;
use LogicException;
use Random\Randomizer;
use ReflectionFunction;
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
     */
    public function testConstruct()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $m = new MethodValue($reflection);
        $this->assertSame('mix', $m->name);
        $this->assertSame($reflection->getFileName(), $m->filename);
        $this->assertSame($reflection->getStartLine(), $m->startline);
        $this->assertSame($reflection->getEndLine(), $m->endline);
        $this->assertFalse($m->internal);
        $this->assertTrue($m->return_reference);
        $this->assertNull($m->docstring);
        $this->assertSame(Value::OPERATOR_STATIC, $m->operator);
        $this->assertSame(Value::ACCESS_PROTECTED, $m->access);
        $this->assertSame(TestClass::class, $m->owner_class);
        $this->assertTrue($m->static);
        $this->assertTrue($m->final);
        $this->assertFalse($m->abstract);
        $this->assertFalse($m->internal);

        $reflection = new ReflectionMethod(ChildTestClass::class, '__construct');
        $parent_reflection = new ReflectionMethod(TestClass::class, '__construct');
        $m = new MethodValue($reflection);
        $this->assertSame($parent_reflection->getDocComment(), $m->docstring);
        $this->assertSame(Value::OPERATOR_OBJECT, $m->operator);
        $this->assertSame(Value::ACCESS_PUBLIC, $m->access);
        $this->assertSame(TestClass::class, $m->owner_class);

        $reflection = new ReflectionMethod(ChildTestClass::class, 'classHint');
        $m = new MethodValue($reflection);
        $this->assertSame(Value::OPERATOR_OBJECT, $m->operator);
        $this->assertSame(Value::ACCESS_PRIVATE, $m->access);
        $this->assertSame(TestClass::class, $m->owner_class);

        $reflection = new ReflectionFunction('explode');
        $m = new MethodValue($reflection);
        $this->assertTrue($m->internal);
        $this->assertSame(Value::OPERATOR_NONE, $m->operator);
        $this->assertSame(Value::ACCESS_NONE, $m->access);
        $this->assertNull($m->owner_class);
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
     * @covers \Kint\Zval\MethodValue::getAccessPath
     * @covers \Kint\Zval\MethodValue::setAccessPathFrom
     */
    public function testSetAccessPathFrom()
    {
        $o = new InstanceValue('$tc', TestClass::class, 'objhash', 314159);
        $o->access_path = '$tc';

        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__construct'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('new \\Kint\\Test\\Fixtures\\TestClass()', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'staticMethod'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('\\Kint\\Test\\Fixtures\\TestClass::staticMethod()', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'finalMethod'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('$tc->finalMethod()', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'mix'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame(
            '\\Kint\\Test\\Fixtures\\TestClass::mix(array &$x, ?Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\')',
            $m->getAccessPath()
        );

        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__clone'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('clone $tc', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__invoke'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('$tc($x)', $m->getAccessPath());

        // Tests both tostring and case insensitivity
        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__tostring'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('__ToStRiNg', $m->name);
        $this->assertSame('(string) $tc', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__get'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertNull($m->getAccessPath());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getValueShort
     */
    public function testGetValueShort()
    {
        $m = new MethodValue(new ReflectionMethod(TestClass::class, '__construct'));
        $this->assertSame(
            'This is a constructor for a TestClass with the first line of the docstring split into two different lines.',
            $m->getValueShort()
        );

        $m->value->contents = '';
        $this->assertNull($m->getValueShort());

        $m = new MethodValue(new ReflectionFunction('explode'));
        $this->assertNull($m->getValueShort());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'arrayHint'));
        $this->assertNull($m->getValueShort());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getModifiers
     */
    public function testGetModifiers()
    {
        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'staticMethod'));
        $this->assertSame('private static', $m->getModifiers());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'finalMethod'));
        $this->assertSame('final public', $m->getModifiers());

        $m = new MethodValue(new ReflectionMethod(Iterator::class, 'current'));
        $this->assertSame('abstract public', $m->getModifiers());

        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'mix'));
        $this->assertSame('final protected static', $m->getModifiers());

        $m = new MethodValue(new ReflectionFunction('explode'));
        $this->assertNull($m->getModifiers());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getAccessPath
     */
    public function testGetAccessPath()
    {
        $m = new MethodValue(new ReflectionMethod(TestClass::class, 'arrayHint'));
        $this->assertNull($m->getAccessPath());
        $m->access_path = '$m->arrayHint';
        $this->assertSame('$m->arrayHint(array $x)', $m->getAccessPath());
    }

    /**
     * @covers \Kint\Zval\MethodValue::__construct
     */
    public function testReturnType()
    {
        $m = new MethodValue(new ReflectionMethod(Php7TestClass::class, 'typeHints'));
        $this->assertSame('self', $m->returntype);

        $m = new MethodValue(new ReflectionMethod(Php71TestClass::class, 'typeHints'));
        $this->assertSame('?self', $m->returntype);

        $m = new MethodValue(new ReflectionMethod(Php71TestClass::class, 'returnTypeHint'));
        $this->assertSame('?Kint\\Test\\Fixtures\\TestClass', $m->returntype);

        if (KINT_PHP80) {
            $m = new MethodValue(new ReflectionMethod(Php8TestClass::class, 'typeHints'));
            $this->assertSame('?static', $m->returntype);
        }
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
    public function testGetPhpDocUrlFunction()
    {
        $m = new MethodValue(new ReflectionFunction('explode'));
        $this->assertSame(
            'https://www.php.net/function.explode',
            $m->getPhpDocUrl()
        );
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
