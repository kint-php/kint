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

use Kint\Zval\InstanceValue;
use Kint\Zval\MethodValue;
use Kint\Zval\Value;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;

/**
 * @coversNothing
 */
class MethodValueTest extends TestCase
{
    /**
     * @covers \Kint\Zval\MethodValue::__construct
     */
    public function testConstruct()
    {
        $reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix');
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
        $this->assertSame('Kint\\Test\\Fixtures\\TestClass', $m->owner_class);
        $this->assertTrue($m->static);
        $this->assertTrue($m->final);
        $this->assertFalse($m->abstract);
        $this->assertFalse($m->internal);

        $reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\ChildTestClass', '__construct');
        $parent_reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__construct');
        $m = new MethodValue($reflection);
        $this->assertSame($parent_reflection->getDocComment(), $m->docstring);
        $this->assertSame(Value::OPERATOR_OBJECT, $m->operator);
        $this->assertSame(Value::ACCESS_PUBLIC, $m->access);
        $this->assertSame('Kint\\Test\\Fixtures\\TestClass', $m->owner_class);

        if (KINT_PHP83) {
            $reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'classHint');
        } else {
            $reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\ChildTestClass', 'classHint');
        }
        $m = new MethodValue($reflection);
        $this->assertSame(Value::OPERATOR_OBJECT, $m->operator);
        $this->assertSame(Value::ACCESS_PRIVATE, $m->access);
        $this->assertSame('Kint\\Test\\Fixtures\\TestClass', $m->owner_class);

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
        $this->expectException('TypeError');

        $m = new MethodValue(new stdClass());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getAccessPath
     * @covers \Kint\Zval\MethodValue::setAccessPathFrom
     */
    public function testSetAccessPathFrom()
    {
        $o = new InstanceValue();
        $o->name = '$tc';
        $o->access_path = '$tc';
        $o->classname = 'Kint\\Test\\Fixtures\\TestClass';

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__construct'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('new \\Kint\\Test\\Fixtures\\TestClass()', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'staticMethod'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('\\Kint\\Test\\Fixtures\\TestClass::staticMethod()', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'finalMethod'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('$tc->finalMethod()', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame(
            '\\Kint\\Test\\Fixtures\\TestClass::mix(array &$x, ?Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\')',
            $m->getAccessPath()
        );

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__clone'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('clone $tc', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__invoke'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('$tc($x)', $m->getAccessPath());

        // Tests both tostring and case insensitivity
        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__tostring'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('__ToStRiNg', $m->name);
        $this->assertSame('(string) $tc', $m->getAccessPath());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__get'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertNull($m->getAccessPath());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getValueShort
     */
    public function testGetValueShort()
    {
        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__construct'));
        $this->assertSame(
            'This is a constructor for a TestClass with the first line of the docstring split into two different lines.',
            $m->getValueShort()
        );

        $m->value->contents = '';
        $this->assertNull($m->getValueShort());

        $m = new MethodValue(new ReflectionFunction('explode'));
        $this->assertNull($m->getValueShort());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'arrayHint'));
        $this->assertNull($m->getValueShort());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getModifiers
     */
    public function testGetModifiers()
    {
        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'staticMethod'));
        $this->assertSame('private static', $m->getModifiers());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'finalMethod'));
        $this->assertSame('final public', $m->getModifiers());

        $m = new MethodValue(new ReflectionMethod('Iterator', 'current'));
        $this->assertSame('abstract public', $m->getModifiers());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix'));
        $this->assertSame('final protected static', $m->getModifiers());

        $m = new MethodValue(new ReflectionFunction('explode'));
        $this->assertNull($m->getModifiers());
    }

    /**
     * @covers \Kint\Zval\MethodValue::getAccessPath
     */
    public function testGetAccessPath()
    {
        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'arrayHint'));
        $this->assertNull($m->getAccessPath());
        $m->access_path = '$m->arrayHint';
        $this->assertSame('$m->arrayHint(array $x)', $m->getAccessPath());
    }

    /**
     * @covers \Kint\Zval\MethodValue::__construct
     */
    public function testReturnType()
    {
        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\Php7TestClass', 'typeHints'));
        $this->assertSame('self', $m->returntype);

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\Php71TestClass', 'typeHints'));
        $this->assertSame('?self', $m->returntype);

        if (KINT_PHP80) {
            $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\Php8TestClass', 'typeHints'));
            $this->assertSame('?static', $m->returntype);
        }
    }

    /**
     * @covers \Kint\Zval\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrl()
    {
        $m = new MethodValue(new ReflectionMethod('Exception', '__construct'));
        $this->assertSame(
            'https://secure.php.net/exception.construct',
            $m->getPhpDocUrl()
        );
    }

    /**
     * @covers \Kint\Zval\MethodValue::getPhpDocUrl
     */
    public function testGetPhpDocUrlParent()
    {
        $m = new MethodValue(new ReflectionMethod('LogicException', 'getMessage'));
        $this->assertSame(
            'https://secure.php.net/exception.getmessage',
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
            'https://secure.php.net/function.explode',
            $m->getPhpDocUrl()
        );
    }
}
