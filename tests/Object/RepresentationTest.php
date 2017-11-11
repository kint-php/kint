<?php

class Kint_Object_RepresentationTest extends KintTestCase
{
    public function testConstruct()
    {
        $r = new Kint_Object_Representation('This is a label');
        $this->assertEquals('This is a label', $r->label);
        $this->assertEquals('this_is_a_label', $r->name);

        $r = new Kint_Object_Representation('This is a label', 'this_is_a_name');
        $this->assertEquals('This is a label', $r->label);
        $this->assertEquals('this_is_a_name', $r->name);

        $r = new Kint_Object_Representation('Test # 3');
        $this->assertEquals('Test # 3', $r->label);
        $this->assertEquals('test_3', $r->name);
    }

    public function testGetLabel()
    {
        $r = new Kint_Object_Representation('This is a label');
        $this->assertEquals('This is a label', $r->getLabel());
        $r->contents = array(1);
        $this->assertEquals('This is a label', $r->getLabel());
        $r->contents[] = 2;
        $r->contents[] = 3;
        $this->assertEquals('This is a label (3)', $r->getLabel());
    }

    public function testLabelIsImplicit()
    {
        $r = new Kint_Object_Representation('This is a label');
        $this->assertEquals(false, $r->labelIsImplicit());
        $r->implicit_label = true;
        $this->assertEquals(true, $r->labelIsImplicit());
    }
}
