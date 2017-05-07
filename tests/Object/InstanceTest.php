<?php

class Kint_Object_InstanceTest extends PHPUnit_Framework_TestCase
{
    public function testSortByHierarchy()
    {
        $this->assertEquals(1, Kint_Object_Instance::sortByHierarchy('TestClass', 'ChildTestClass'));
        $this->assertEquals(-1, Kint_Object_Instance::sortByHierarchy('ChildTestClass', 'TestClass'));
        $this->assertEquals(0, Kint_Object_Instance::sortByHierarchy('TestClass', 'TestClass'));

        $p = new Kint_Parser();
        $b = Kint_Object::blank('$v');

        $tc = new TestClass();
        $ctc = new ChildTestClass();

        $tc = $p->parse($tc, clone $b);
        $ctc = $p->parse($ctc, clone $b);

        $this->assertEquals(1, Kint_Object_Instance::sortByHierarchy($tc, $ctc));
        $this->assertEquals(-1, Kint_Object_Instance::sortByHierarchy($ctc, $tc));
        $this->assertEquals(0, Kint_Object_Instance::sortByHierarchy($tc, $tc));
    }

    public function testTransplant()
    {
        $p = new Kint_Parser();
        $b = Kint_Object::blank('$v');
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $o2 = $o->transplant(new Kint_Object_Instance());

        $o->hints[] = 'object';

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
    }

    public function testGetType()
    {
        $p = new Kint_Parser();
        $b = Kint_Object::blank('$v');
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertEquals('ChildTestClass', $o->getType());
    }
}
