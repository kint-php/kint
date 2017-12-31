<?php

namespace Kint\Test\Object;

use Kint\Object\BasicObject;
use Kint\Object\InstanceObject;
use Kint\Object\MethodObject;
use PHPUnit_Framework_TestCase;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;

class MethodObjectTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \Kint\Object\MethodObject::__construct
     */
    public function testConstruct()
    {
        $reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix');
        $m = new MethodObject($reflection);
        $this->assertEquals('mix', $m->name);
        $this->assertEquals($reflection->getFileName(), $m->filename);
        $this->assertEquals($reflection->getStartLine(), $m->startline);
        $this->assertEquals($reflection->getEndLine(), $m->endline);
        $this->assertEquals(false, $m->internal);
        $this->assertEquals(true, $m->return_reference);
        $this->assertEquals($reflection->getDocComment(), $m->docstring);
        $this->assertEquals(BasicObject::OPERATOR_STATIC, $m->operator);
        $this->assertEquals(BasicObject::ACCESS_PROTECTED, $m->access);
        $this->assertEquals('Kint\\Test\\Fixtures\\TestClass', $m->owner_class);
        $this->assertTrue($m->static);
        $this->assertTrue($m->final);
        $this->assertFalse($m->abstract);
        $this->assertFalse($m->internal);

        $reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\ChildTestClass', '__construct');
        $parent_reflection = new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__construct');
        $m = new MethodObject($reflection);
        $this->assertEquals($parent_reflection->getDocComment(), $m->docstring);
        $this->assertEquals(BasicObject::OPERATOR_OBJECT, $m->operator);
        $this->assertEquals(BasicObject::ACCESS_PUBLIC, $m->access);
        $this->assertEquals('Kint\\Test\\Fixtures\\TestClass', $m->owner_class);

        $reflection = new ReflectionFunction('explode');
        $m = new MethodObject($reflection);
        $this->assertTrue($m->internal);
        $this->assertEquals(BasicObject::OPERATOR_NONE, $m->operator);
        $this->assertEquals(BasicObject::ACCESS_NONE, $m->access);
        $this->assertEquals(null, $m->owner_class);
    }

    /**
     * @covers \Kint\Object\MethodObject::__construct
     */
    public function testConstructWrongType()
    {
        if (KINT_PHP70) {
            $this->setExpectedException('TypeError');
        } else {
            $this->setExpectedException('PHPUnit_Framework_Error');
        }
        $m = new MethodObject(new stdClass());
    }

    /**
     * @covers \Kint\Object\MethodObject::setAccessPathFrom
     * @covers \Kint\Object\MethodObject::getAccessPath
     */
    public function testSetAccessPathFrom()
    {
        $o = BasicObject::blank('$tc');
        $o = $o->transplant(new InstanceObject());
        $o->classname = 'Kint\\Test\\Fixtures\\TestClass';

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__construct'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals('new \\Kint\\Test\\Fixtures\\TestClass()', $m->getAccessPath());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'staticMethod'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals('\\Kint\\Test\\Fixtures\\TestClass::staticMethod()', $m->getAccessPath());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'finalMethod'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals('$tc->finalMethod()', $m->getAccessPath());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals(
            '\\Kint\\Test\\Fixtures\\TestClass::mix(array &$x, Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\')',
            $m->getAccessPath()
        );

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__clone'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals('clone $tc', $m->getAccessPath());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__invoke'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals('$tc($x)', $m->getAccessPath());

        // Tests both tostring and case insensitivity
        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', '__tostring'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals('__ToStRiNg', $m->name);
        $this->assertEquals('(string) $tc', $m->getAccessPath());

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
        $this->assertEquals(
            'This is a constructor for a TestClass with the first line of the docstring split into two different lines.',
            $m->getValueShort()
        );

        $m = new MethodObject(new ReflectionFunction('explode'));
        $this->assertNull($m->getValueShort());
    }

    /**
     * @covers \Kint\Object\MethodObject::getModifiers
     */
    public function testGetModifiers()
    {
        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'staticMethod'));
        $this->assertEquals('private static', $m->getModifiers());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'finalMethod'));
        $this->assertEquals('final public', $m->getModifiers());

        $m = new MethodObject(new ReflectionMethod('ReflectionFunctionAbstract', '__toString'));
        $this->assertEquals('abstract public', $m->getModifiers());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix'));
        $this->assertEquals('final protected static', $m->getModifiers());
    }

    /**
     * @covers \Kint\Object\MethodObject::getAccessPath
     */
    public function testGetAccessPath()
    {
        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'arrayHint'));
        $this->assertNull($m->getAccessPath());
        $m->access_path = '$m->arrayHint';
        $this->assertEquals('$m->arrayHint(array $x)', $m->getAccessPath());
    }

    /**
     * @covers \Kint\Object\MethodObject::getParams
     */
    public function testGetParams()
    {
        $m = new MethodObject(new ReflectionFunction('explode'));
        if (defined('HHVM_VERSION')) {
            $this->assertStringStartsWith('HH\\string $delimiter, HH\\string $str, HH\\int $limit = ', $m->getParams());
        } else {
            $this->assertEquals('$separator, $str, $limit', $m->getParams());
        }

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'arrayHint'));
        $this->assertEquals('array $x', $m->getParams());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'classHint'));
        $this->assertEquals('Kint\\Test\\Fixtures\\TestClass $x', $m->getParams());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'ref'));
        $this->assertEquals('&$x', $m->getParams());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'defaultMethod'));
        $this->assertEquals('$x = 1234', $m->getParams());

        $m = new MethodObject(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix'));
        $this->assertEquals(
            'array &$x, Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\'',
            $m->getParams()
        );
    }

    /**
     * @covers \Kint\Object\MethodObject::getPhpDocUrl
     */
    public function testGetPhpDocUrl()
    {
        $m = new MethodObject(new ReflectionMethod('ReflectionMethod', '__construct'));
        $this->assertEquals(
            'https://secure.php.net/reflectionmethod.construct',
            $m->getPhpDocUrl()
        );
    }

    /**
     * @covers \Kint\Object\MethodObject::getPhpDocUrl
     */
    public function testGetPhpDocUrlParent()
    {
        $m = new MethodObject(new ReflectionMethod('ReflectionMethod', '__clone'));
        $this->assertEquals(
            'https://secure.php.net/reflectionfunctionabstract.clone',
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
}
