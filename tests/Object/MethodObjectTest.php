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
use Kint\Object\MethodObject;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;

class MethodObjectTest extends TestCase
{
    /**
     * @covers \Kint\Object\MethodObject::__construct
     */
    public function testConstruct()
    {
        $reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix');
        $m = new MethodObject($reflection);
        $this->assertSame('mix', $m->name);
        $this->assertSame($reflection->getFileName(), $m->filename);
        $this->assertSame($reflection->getStartLine(), $m->startline);
        $this->assertSame($reflection->getEndLine(), $m->endline);
        $this->assertFalse($m->internal);
        $this->assertTrue($m->return_reference);
        $this->assertSame($reflection->getDocComment(), $m->docstring);
        $this->assertSame(BasicObject::OPERATOR_STATIC, $m->operator);
        $this->assertSame(BasicObject::ACCESS_PROTECTED, $m->access);
        $this->assertSame('Kint\\Test\\Fixtures\\TestClass', $m->owner_class);
        $this->assertTrue($m->static);
        $this->assertTrue($m->final);
        $this->assertFalse($m->abstract);
        $this->assertFalse($m->internal);

        $reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\ChildTestClass', '__construct');
        $parent_reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__construct');
        $m = new MethodObject($reflection);
        $this->assertSame($parent_reflection->getDocComment(), $m->docstring);
        $this->assertSame(BasicObject::OPERATOR_OBJECT, $m->operator);
        $this->assertSame(BasicObject::ACCESS_PUBLIC, $m->access);
        $this->assertSame('Kint\\Test\\Fixtures\\TestClass', $m->owner_class);

        $reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\ChildTestClass', 'classHint');
        $m = new MethodObject($reflection);
        $this->assertSame(BasicObject::OPERATOR_OBJECT, $m->operator);
        $this->assertSame(BasicObject::ACCESS_PRIVATE, $m->access);
        $this->assertSame('Kint\\Test\\Fixtures\\TestClass', $m->owner_class);

        $reflection = new ReflectionFunction('explode');
        $m = new MethodObject($reflection);
        $this->assertTrue($m->internal);
        $this->assertSame(BasicObject::OPERATOR_NONE, $m->operator);
        $this->assertSame(BasicObject::ACCESS_NONE, $m->access);
        $this->assertNull($m->owner_class);
    }

    /**
     * @covers \Kint\Object\MethodObject::__construct
     */
    public function testConstructWrongType()
    {
        if (KINT_PHP70) {
            $exception = 'TypeError';
        } else {
            $exception = 'PHPUnit_Framework_Error';
        }

        if (\method_exists(__CLASS__, 'expectException')) {
            $this->expectException($exception);
        } else {
            $this->setExpectedException($exception);
        }

        $m = new MethodObject(new stdClass());
    }

    /**
     * @covers \Kint\Object\MethodObject::getAccessPath
     * @covers \Kint\Object\MethodObject::setAccessPathFrom
     */
    public function testSetAccessPathFrom()
    {
        $o = new InstanceObject();
        $o->name = '$tc';
        $o->access_path = '$tc';
        $o->classname = 'Kint\\Test\\Fixtures\\TestClass';

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__construct'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('new \\Kint\\Test\\Fixtures\\TestClass()', $m->getAccessPath());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'staticMethod'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('\\Kint\\Test\\Fixtures\\TestClass::staticMethod()', $m->getAccessPath());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'finalMethod'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('$tc->finalMethod()', $m->getAccessPath());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame(
            '\\Kint\\Test\\Fixtures\\TestClass::mix(array &$x, Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\')',
            $m->getAccessPath()
        );

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__clone'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('clone $tc', $m->getAccessPath());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__invoke'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('$tc($x)', $m->getAccessPath());

        // Tests both tostring and case insensitivity
        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__tostring'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertSame('__ToStRiNg', $m->name);
        $this->assertSame('(string) $tc', $m->getAccessPath());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__get'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertNull($m->getAccessPath());
    }

    /**
     * @covers \Kint\Object\MethodObject::getValueShort
     */
    public function testGetValueShort()
    {
        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__construct'));
        $this->assertSame(
            'This is a constructor for a TestClass with the first line of the docstring split into two different lines.',
            $m->getValueShort()
        );

        $m->value->contents = '';
        $this->assertNull($m->getValueShort());

        $m = new MethodObject(new ReflectionFunction('explode'));
        $this->assertNull($m->getValueShort());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'arrayHint'));
        $this->assertNull($m->getValueShort());
    }

    /**
     * @covers \Kint\Object\MethodObject::getModifiers
     */
    public function testGetModifiers()
    {
        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'staticMethod'));
        $this->assertSame('private static', $m->getModifiers());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'finalMethod'));
        $this->assertSame('final public', $m->getModifiers());

        $m = new MethodObject(new ReflectionMethod('Iterator', 'current'));
        $this->assertSame('abstract public', $m->getModifiers());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix'));
        $this->assertSame('final protected static', $m->getModifiers());

        $m = new MethodObject(new ReflectionFunction('explode'));
        $this->assertNull($m->getModifiers());
    }

    /**
     * @covers \Kint\Object\MethodObject::getAccessPath
     */
    public function testGetAccessPath()
    {
        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'arrayHint'));
        $this->assertNull($m->getAccessPath());
        $m->access_path = '$m->arrayHint';
        $this->assertSame('$m->arrayHint(array $x)', $m->getAccessPath());
    }

    /**
     * @covers \Kint\Object\MethodObject::getParams
     */
    public function testGetParams()
    {
        $m = new MethodObject(new ReflectionFunction('explode'));
        if (\version_compare(PHP_VERSION, '8') >= 0) {
            $this->assertSame('string $separator, string $str, int $limit', $m->getParams());
        } else {
            $this->assertSame('$separator, $str, $limit', $m->getParams());
        }

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'arrayHint'));
        $this->assertSame('array $x', $m->getParams());

        // Testing cache
        $m->parameters = [];
        $this->assertSame('array $x', $m->getParams());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'classHint'));
        $this->assertSame('Kint\\Test\\Fixtures\\TestClass $x', $m->getParams());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'ref'));
        $this->assertSame('&$x', $m->getParams());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'defaultMethod'));
        $this->assertSame('$x = 1234', $m->getParams());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix'));
        $this->assertSame(
            'array &$x, Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\'',
            $m->getParams()
        );
    }

    /**
     * @covers \Kint\Object\MethodObject::getParams
     */
    public function testGetParamsPhp7()
    {
        if (!KINT_PHP70) {
            $this->markTestSkipped('Not testing PHP7+ parameter type hints on PHP5');
        }

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\Php7TestClass', 'typeHints'));
        $this->assertSame('string $p1, int $p2, bool $p3 = false', $m->getParams());
    }

    /**
     * @covers \Kint\Object\MethodObject::__construct
     */
    public function testReturnType()
    {
        if (!KINT_PHP70) {
            $this->markTestSkipped('Not testing PHP7+ return type hints on PHP5');
        }

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\Php7TestClass', 'typeHints'));
        $this->assertSame('self', $m->returntype);
    }

    /**
     * @covers \Kint\Object\MethodObject::getPhpDocUrl
     */
    public function testGetPhpDocUrl()
    {
        $m = new MethodObject(new ReflectionMethod('Exception', '__construct'));
        $this->assertSame(
            'https://secure.php.net/exception.construct',
            $m->getPhpDocUrl()
        );
    }

    /**
     * @covers \Kint\Object\MethodObject::getPhpDocUrl
     */
    public function testGetPhpDocUrlParent()
    {
        $m = new MethodObject(new ReflectionMethod('LogicException', 'getMessage'));
        $this->assertSame(
            'https://secure.php.net/exception.getmessage',
            $m->getPhpDocUrl()
        );
    }

    /**
     * @covers \Kint\Object\MethodObject::getPhpDocUrl
     */
    public function testGetPhpDocUrlUserDefined()
    {
        $m = new MethodObject(new ReflectionMethod(__CLASS__, __FUNCTION__));
        $this->assertNull($m->getPhpDocUrl());
    }

    /**
     * @covers \Kint\Object\MethodObject::getPhpDocUrl
     */
    public function testGetPhpDocUrlFunction()
    {
        $m = new MethodObject(new ReflectionFunction('explode'));
        $this->assertSame(
            'https://secure.php.net/function.explode',
            $m->getPhpDocUrl()
        );
    }
}
