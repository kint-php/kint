<?php

namespace Kint\Test\Object;

use Kint\Object\BasicObject;
use Kint\Object\InstanceObject;
use Kint\Parser\Parser;
use Kint\Test\Stub\ChildTestClass;
use Kint\Test\Stub\TestClass;
use PHPUnit_Framework_TestCase;

class InstanceObjectTest extends PHPUnit_Framework_TestCase
{
    public function testSortByHierarchy()
    {
        $this->assertEquals(1, InstanceObject::sortByHierarchy('Kint\\Test\\Stub\\TestClass', 'Kint\\Test\\Stub\\ChildTestClass'));
        $this->assertEquals(-1, InstanceObject::sortByHierarchy('Kint\\Test\\Stub\\ChildTestClass', 'Kint\\Test\\Stub\\TestClass'));
        $this->assertEquals(0, InstanceObject::sortByHierarchy('Kint\\Test\\Stub\\TestClass', 'Kint\\Test\\Stub\\TestClass'));

        $p = new Parser();
        $b = BasicObject::blank('$v');

        $tc = new TestClass();
        $ctc = new ChildTestClass();

        $tc = $p->parse($tc, clone $b);
        $ctc = $p->parse($ctc, clone $b);

        $this->assertEquals(1, InstanceObject::sortByHierarchy($tc, $ctc));
        $this->assertEquals(-1, InstanceObject::sortByHierarchy($ctc, $tc));
        $this->assertEquals(0, InstanceObject::sortByHierarchy($tc, $tc));
    }

    public function testTransplant()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $o2 = $o->transplant(new InstanceObject());

        $o->hints[] = 'object';

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
    }

    public function testGetType()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertEquals('Kint\\Test\\Stub\\ChildTestClass', $o->getType());
    }
}
