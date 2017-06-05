<?php

class Kint_Object_MethodTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $reflection = new ReflectionMethod('TestClass', 'mix');
        $m = new Kint_Object_Method($reflection);
        $this->assertEquals('mix', $m->name);
        $this->assertEquals($reflection->getFilename(), $m->filename);
        $this->assertEquals($reflection->getStartLine(), $m->startline);
        $this->assertEquals($reflection->getEndLine(), $m->endline);
        $this->assertEquals(false, $m->internal);
        $this->assertEquals($reflection->getDocComment(), $m->docstring);
        $this->assertEquals(Kint_Object::OPERATOR_STATIC, $m->operator);
        $this->assertEquals(Kint_Object::ACCESS_PROTECTED, $m->access);
        $this->assertEquals('TestClass', $m->owner_class);
        $this->assertTrue($m->static);
        $this->assertTrue($m->final);
        $this->assertFalse($m->abstract);
        $this->assertFalse($m->internal);

        $reflection = new ReflectionMethod('ChildTestClass', '__construct');
        $parent_reflection = new ReflectionMethod('TestClass', '__construct');
        $m = new Kint_Object_Method($reflection);
        $this->assertEquals($parent_reflection->getDocComment(), $m->docstring);
        $this->assertEquals(Kint_Object::OPERATOR_OBJECT, $m->operator);
        $this->assertEquals(Kint_Object::ACCESS_PUBLIC, $m->access);
        $this->assertEquals('TestClass', $m->owner_class);

        $reflection = new ReflectionFunction('explode');
        $m = new Kint_Object_Method($reflection);
        $this->assertTrue($m->internal);
        $this->assertEquals(Kint_Object::OPERATOR_NONE, $m->operator);
        $this->assertEquals(Kint_Object::ACCESS_NONE, $m->access);
        $this->assertEquals(null, $m->owner_class);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWrongType()
    {
        $m = new Kint_Object_Method(new stdClass());
    }

    public function testSetAccessPathFrom()
    {
        $o = Kint_Object::blank('$tc');
        $o = $o->transplant(new Kint_Object_Instance());
        $o->classname = 'TestClass';

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', '__construct'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals('new \\TestClass()', $m->getAccessPath());

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'static_method'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals('\\TestClass::static_method()', $m->getAccessPath());

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'final_method'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals('$tc->final_method()', $m->getAccessPath());

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'mix'));
        $this->assertNull($m->getAccessPath());
        $m->setAccessPathFrom($o);
        $this->assertEquals(
            '\\TestClass::mix(array &$x, TestClass $y = null, $z = array(...), $_ = \'string\')',
            $m->getAccessPath()
        );
    }

    public function testGetValueShort()
    {
        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', '__construct'));
        $this->assertEquals(
            'This is a constructor for a TestClass with the first line of the docstring split into two different lines.',
            $m->getValueShort()
        );
    }

    public function testGetModifiers()
    {
        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'static_method'));
        $this->assertEquals('private static', $m->getModifiers());

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'final_method'));
        $this->assertEquals('final public', $m->getModifiers());

        $m = new Kint_Object_Method(new ReflectionMethod('ReflectionFunctionAbstract', '__toString'));
        $this->assertEquals('abstract public', $m->getModifiers());

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'mix'));
        $this->assertEquals('final protected static', $m->getModifiers());
    }

    public function testGetAccessPath()
    {
        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'array_hint'));
        $this->assertNull($m->getAccessPath());
        $m->access_path = '$m->array_hint';
        $this->assertEquals('$m->array_hint(array $x)', $m->getAccessPath());
    }

    public function testGetParams()
    {
        $m = new Kint_Object_Method(new ReflectionFunction('explode'));
        if (defined('HHVM_VERSION')) {
            $this->assertStringStartsWith('HH\\string $delimiter, HH\\string $str, HH\\int $limit = ', $m->getParams());
        } else {
            $this->assertEquals('$separator, $str, $limit', $m->getParams());
        }

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'array_hint'));
        $this->assertEquals('array $x', $m->getParams());

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'class_hint'));
        $this->assertEquals('TestClass $x', $m->getParams());

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'ref'));
        $this->assertEquals('&$x', $m->getParams());

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'default_method'));
        $this->assertEquals('$x = 1234', $m->getParams());

        $m = new Kint_Object_Method(new ReflectionMethod('TestClass', 'mix'));
        $this->assertEquals(
            'array &$x, TestClass $y = null, $z = array(...), $_ = \'string\'',
            $m->getParams()
        );
    }

    public function testGetPhpDocUrl()
    {
        $m = new Kint_Object_Method(new ReflectionMethod('ReflectionMethod', '__construct'));
        $this->assertEquals(
            'https://secure.php.net/manual/en/reflectionmethod.construct.php',
            $m->getPhpDocUrl()
        );
    }

    public function testGetPhpDocUrlParent()
    {
        $m = new Kint_Object_Method(new ReflectionMethod('ReflectionMethod', '__clone'));
        $this->assertEquals(
            'https://secure.php.net/manual/en/reflectionfunctionabstract.clone.php',
            $m->getPhpDocUrl()
        );
    }

    public function testGetPhpDocUrlUserDefined()
    {
        $m = new Kint_Object_Method(new ReflectionMethod(__CLASS__, __FUNCTION__));
        $this->assertNull($m->getPhpDocUrl());
    }
}
