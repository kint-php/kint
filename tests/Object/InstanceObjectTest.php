<?php

namespace Kint\Test\Object;

use Kint\Object\BasicObject;
use Kint\Object\InstanceObject;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\Fixtures\TestClass;
use PHPUnit_Framework_TestCase;

class InstanceObjectTest extends PHPUnit_Framework_TestCase
{
    public function testSortByHierarchy()
    {
        $this->assertEquals(1, InstanceObject::sortByHierarchy('Kint\\Test\\Fixtures\\TestClass', 'Kint\\Test\\Fixtures\\ChildTestClass'));
        $this->assertEquals(-1, InstanceObject::sortByHierarchy('Kint\\Test\\Fixtures\\ChildTestClass', 'Kint\\Test\\Fixtures\\TestClass'));
        $this->assertEquals(0, InstanceObject::sortByHierarchy('Kint\\Test\\Fixtures\\TestClass', 'Kint\\Test\\Fixtures\\TestClass'));

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

        $this->assertEquals('Kint\\Test\\Fixtures\\ChildTestClass', $o->getType());
    }
}
