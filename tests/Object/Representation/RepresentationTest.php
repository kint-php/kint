<?php

namespace Kint\Test\Object\Representation;

use Kint\Object\Representation\Representation;
use Kint\Test\KintTestCase;

class RepresentationTest extends KintTestCase
{
    public function testConstruct()
    {
        $r = new Representation('This is a label');
        $this->assertEquals('This is a label', $r->label);
        $this->assertEquals('this_is_a_label', $r->getName());

        $r = new Representation('This is a label', 'this_is_a_name');
        $this->assertEquals('This is a label', $r->label);
        $this->assertEquals('this_is_a_name', $r->getName());

        $r = new Representation('Test # 3');
        $this->assertEquals('Test # 3', $r->label);
        $this->assertEquals('test_3', $r->getName());
    }

    public function testGetLabel()
    {
        $r = new Representation('This is a label');
        $this->assertEquals('This is a label', $r->getLabel());
        $r->contents = array(1);
        $this->assertEquals('This is a label', $r->getLabel());
        $r->contents[] = 2;
        $r->contents[] = 3;
        $this->assertEquals('This is a label (3)', $r->getLabel());
    }

    public function testSetName()
    {
        $r = new Representation('Test');
        $this->assertEquals('test', $r->getName());

        $r->setName('Test this string!');
        $this->assertEquals('test_this_string_', $r->getName());

        $r->setName('UPPERCASE HOLDS NO POWER HERE');
        $this->assertEquals('uppercase_holds_no_power_here', $r->getName());

        $r->setName('Multiple ! # () @!$#%@#$% special characters');
        $this->assertEquals('multiple_special_characters', $r->getName());

        $r->setName('But numbers work like 123');
        $this->assertEquals('but_numbers_work_like_123', $r->getName());
    }

    public function testLabelIsImplicit()
    {
        $r = new Representation('This is a label');
        $this->assertEquals(false, $r->labelIsImplicit());
        $r->implicit_label = true;
        $this->assertEquals(true, $r->labelIsImplicit());
    }
}
