<?php

class Kint_ObjectTest extends PHPUnit_Framework_TestCase
{
    public function testAddRepresentation()
    {
        $o = new Kint_Object();

        $this->assertSame(array(), $o->representations);
        $this->assertSame(null, $o->value);

        $this->assertTrue($o->addRepresentation($r1 = new Kint_Object_Representation('Rep 1')));
        $this->assertSame(
            array(
                $r1->name => $r1,
            ),
            $o->representations
        );
        $this->assertSame($r1, $o->value);

        $this->assertFalse($o->addRepresentation($r1));
        $this->assertSame(
            array(
                $r1->name => $r1,
            ),
            $o->representations
        );

        $this->assertTrue($o->addRepresentation($r2 = new Kint_Object_Representation('Rep 2')));
        $this->assertSame(
            array(
                $r1->name => $r1,
                $r2->name => $r2,
            ),
            $o->representations
        );

        $this->assertTrue($o->addRepresentation($r3 = new Kint_Object_Representation('Rep 3'), 0));
        $this->assertSame(
            array(
                $r3->name => $r3,
                $r1->name => $r1,
                $r2->name => $r2,
            ),
            $o->representations
        );

        $this->assertTrue($o->addRepresentation($r4 = new Kint_Object_Representation('Rep 4'), 1));
        $this->assertSame(
            array(
                $r3->name => $r3,
                $r4->name => $r4,
                $r1->name => $r1,
                $r2->name => $r2,
            ),
            $o->representations
        );

        $this->assertTrue($o->addRepresentation($r5 = new Kint_Object_Representation('Rep 5'), 100));
        $this->assertSame(
            array(
                $r3->name => $r3,
                $r4->name => $r4,
                $r1->name => $r1,
                $r2->name => $r2,
                $r5->name => $r5,
            ),
            $o->representations
        );

        $this->assertTrue($o->addRepresentation($r6 = new Kint_Object_Representation('Rep 6'), -100));
        $this->assertSame(
            array(
                $r6->name => $r6,
                $r3->name => $r3,
                $r4->name => $r4,
                $r1->name => $r1,
                $r2->name => $r2,
                $r5->name => $r5,
            ),
            $o->representations
        );

        $this->assertSame($r1, $o->value);
    }

    public function testReplaceRepresentation()
    {
        $o = new Kint_Object();
        $o->addRepresentation($r1 = new Kint_Object_Representation('Rep 1'));
        $o->addRepresentation($r2 = new Kint_Object_Representation('Rep 2'));
        $o->addRepresentation($r3 = new Kint_Object_Representation('Rep 3'));

        $this->assertSame(
            array(
                $r1->name => $r1,
                $r2->name => $r2,
                $r3->name => $r3,
            ),
            $o->representations
        );

        $o->replaceRepresentation($r2_2 = new Kint_Object_Representation('Rep 2'));

        $this->assertSame(
            array(
                $r1->name => $r1,
                $r2_2->name => $r2_2,
                $r3->name => $r3,
            ),
            $o->representations
        );

        $o->replaceRepresentation($r2, 0);

        $this->assertSame(
            array(
                $r2->name => $r2,
                $r1->name => $r1,
                $r3->name => $r3,
            ),
            $o->representations
        );

        $o->replaceRepresentation($r2_2, 1);

        $this->assertSame(
            array(
                $r1->name => $r1,
                $r2_2->name => $r2_2,
                $r3->name => $r3,
            ),
            $o->representations
        );
    }

    public function testRemoveRepresentation()
    {
        $o = new Kint_Object();
        $o->addRepresentation($r1 = new Kint_Object_Representation('Rep 1'));
        $o->addRepresentation($r2 = new Kint_Object_Representation('Rep 2'));
        $o->addRepresentation($r3 = new Kint_Object_Representation('Rep 3'));

        $this->assertSame(
            array(
                $r1->name => $r1,
                $r2->name => $r2,
                $r3->name => $r3,
            ),
            $o->representations
        );

        $o->removeRepresentation($r2->name);

        $this->assertSame(
            array(
                $r1->name => $r1,
                $r3->name => $r3,
            ),
            $o->representations
        );
    }

    public function testGetRepresentation()
    {
        $o = new Kint_Object();
        $o->addRepresentation($r1 = new Kint_Object_Representation('Rep 1'));
        $o->addRepresentation($r2 = new Kint_Object_Representation('Rep 2'));
        $o->addRepresentation($r3 = new Kint_Object_Representation('Rep 3'));

        $this->assertSame($r1, $o->getRepresentation($r1->name));
        $this->assertSame($r2, $o->getRepresentation($r2->name));
        $this->assertSame($r3, $o->getRepresentation($r3->name));
        $this->assertSame(null, $o->getRepresentation('Non-existant representation name'));
    }

    public function testGetRepresentations()
    {
        $o = new Kint_Object();
        $o->addRepresentation($r1 = new Kint_Object_Representation('Rep 1'));
        $o->addRepresentation($r2 = new Kint_Object_Representation('Rep 2'));
        $o->addRepresentation($r3 = new Kint_Object_Representation('Rep 3'));

        $this->assertSame(
            array(
                $r1->name => $r1,
                $r2->name => $r2,
                $r3->name => $r3,
            ),
            $o->getRepresentations()
        );
    }

    public function testClearRepresentations()
    {
        $o = new Kint_Object();
        $o->addRepresentation($r1 = new Kint_Object_Representation('Rep 1'));
        $o->addRepresentation(new Kint_Object_Representation('Rep 2'));
        $o->addRepresentation(new Kint_Object_Representation('Rep 3'));

        $o->clearRepresentations();

        $this->assertSame(array(), $o->representations);
        $this->assertSame($r1, $o->value);
    }

    public function testGetType()
    {
        $o = new Kint_Object();
        $o->type = 'array';
        $this->assertEquals('array', $o->getType());
    }

    public function modifierProvider()
    {
        return array(
            'public' => array(
                false,
                false,
                Kint_Object::ACCESS_PUBLIC,
                'public',
            ),
            'public const' => array(
                true,
                false,
                Kint_Object::ACCESS_PUBLIC,
                'public const',
            ),
            'public static' => array(
                false,
                true,
                Kint_Object::ACCESS_PUBLIC,
                'public static',
            ),
            'protected' => array(
                false,
                false,
                Kint_Object::ACCESS_PROTECTED,
                'protected',
            ),
            'private' => array(
                false,
                false,
                Kint_Object::ACCESS_PRIVATE,
                'private',
            ),
            'none' => array(
                false,
                false,
                Kint_Object::ACCESS_NONE,
                null,
            ),
            'private static' => array(
                false,
                true,
                Kint_Object::ACCESS_PRIVATE,
                'private static',
            ),
            'public const static' => array(
                true,
                true,
                Kint_Object::ACCESS_PUBLIC,
                'public const static',
            ),
            'const' => array(
                true,
                false,
                Kint_Object::ACCESS_NONE,
                'const',
            ),
        );
        $accesses = array(
            Kint_Object::ACCESS_NONE,
            Kint_Object::ACCESS_PUBLIC,
            Kint_Object::ACCESS_PROTECTED,
            Kint_Object::ACCESS_PRIVATE,
        );
    }

    /**
     * @dataProvider modifierProvider
     */
    public function testGetModifiers($const, $static, $access, $expect)
    {
        $o = new Kint_Object();
        $o->const = $const;
        $o->static = $static;
        $o->access = $access;
        $this->assertSame($expect, $o->getModifiers());
    }

    public function testGetAccess()
    {
        $o = new Kint_Object();
        $this->assertNull($o->getAccess());
        $o->access = Kint_Object::ACCESS_PUBLIC;
        $this->assertEquals('public', $o->getAccess());
        $o->access = Kint_Object::ACCESS_PROTECTED;
        $this->assertEquals('protected', $o->getAccess());
        $o->access = Kint_Object::ACCESS_PRIVATE;
        $this->assertEquals('private', $o->getAccess());
    }

    public function testGetName()
    {
        $o = new Kint_Object();
        $o->name = '$var';
        $this->assertEquals('$var', $o->getName());
        $o->name = '($a + $b)';
        $this->assertEquals('($a + $b)', $o->getName());
        $o->name = 'This is just a name, nothing more, nothing less.';
        $this->assertEquals('This is just a name, nothing more, nothing less.', $o->getName());
    }

    public function testGetOperator()
    {
        $o = new Kint_Object();
        $this->assertNull($o->getOperator());
        $o->operator = Kint_Object::OPERATOR_NONE;
        $this->assertNull($o->getOperator());
        $o->operator = Kint_Object::OPERATOR_ARRAY;
        $this->assertEquals('=>', $o->getOperator());
        $o->operator = Kint_Object::OPERATOR_OBJECT;
        $this->assertEquals('->', $o->getOperator());
        $o->operator = Kint_Object::OPERATOR_STATIC;
        $this->assertEquals('::', $o->getOperator());
    }

    public function testGetSize()
    {
        $o = new Kint_Object();
        $this->assertNull($o->getSize());
        $o->size = 0;
        $this->assertEquals(0, $o->getSize());
        $o->size = 42;
        $this->assertEquals(42, $o->getSize());
    }

    public function testGetValueShort()
    {
        $o = new Kint_Object();
        $r = new Kint_Object_Representation('contents');
        $o->addRepresentation($r);

        $r->contents = true;
        $this->assertNull($o->getValueShort());
        $o->type = 'boolean';
        $this->assertEquals('true', $o->getValueShort());
        $r->contents = false;
        $this->assertEquals('false', $o->getValueShort());
        $o->type = 'integer';
        $r->contents = 1234;
        $this->assertEquals(1234, $o->getValueShort());
        $o->type = 'double';
        $r->contents = 1234.5678;
        $this->assertEquals(1234.5678, $o->getValueShort());
        $o->type = 'array';
        $r->contents = array();
        $this->assertNull($o->getValueShort());
        $o->type = 'string';
        $r->contents = 'string';
        $this->assertNull($o->getValueShort());
    }

    public function testGetAccessPath()
    {
        $o = new Kint_Object();
        $this->assertNull($o->getAccessPath());
        $o->access_path = 'abcdefg, hijk elemeno p';
        $this->assertEquals('abcdefg, hijk elemeno p', $o->getAccessPath());
    }

    public function testBlank()
    {
        $o = new Kint_Object();
        $this->assertNull($o->name);
        $this->assertNull($o->access_path);

        $o = Kint_Object::blank();
        $this->assertNull($o->name);
        $this->assertNull($o->access_path);

        $o = Kint_Object::blank('$var');
        $this->assertEquals('$var', $o->name);
        $this->assertEquals('$var', $o->access_path);

        $o = Kint_Object::blank('Name', 'access_path');
        $this->assertEquals('Name', $o->name);
        $this->assertEquals('access_path', $o->access_path);
    }

    public function testTransplant()
    {
        $o = new Kint_Object();

        $o->name = 'name';
        $o->size = 42;
        $o->access_path = 'access_path';
        $o->access = Kint_Object::ACCESS_PUBLIC;
        $o->static = true;
        $o->const = true;
        $o->type = 'type';
        $o->depth = 43;
        $o->owner_class = 'owner_class';
        $o->operator = Kint_Object::OPERATOR_OBJECT;
        $o->reference = true;
        $o->hints = array('test', 'transplant', 'hints');

        $r = new Kint_Object_Representation('Test');
        $o->addRepresentation($r);

        $o2 = $o->transplant(new Kint_Object());

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
        $this->assertSame($o->value, $o2->value);

        $o2 = new Kint_Object();
        $r2 = new Kint_Object_Representation('Test 2');
        $o2->addRepresentation($r2);
        $o2->hints = array('test', 'thoroughly');

        $o2 = $o->transplant($o2);

        $this->assertSame(array('test_2' => $r2, 'test' => $r), $o2->representations);
        $this->assertSame(array('test', 'transplant', 'hints', 'test', 'thoroughly'), $o2->hints);
    }

    public function testIsSequential()
    {
        $this->assertTrue(Kint_Object::isSequential(array(1, 2, 3, 4)));
        $this->assertFalse(Kint_Object::isSequential(array(0 => 1, 1 => 2, 3 => 3, 2 => 4)));
        $this->assertFalse(Kint_Object::isSequential(array(0 => 1, 1 => 2, '02' => 3, 3 => 4)));
    }

    public function testSortByAccess()
    {
        $o1 = new Kint_Object();
        $o2 = new Kint_Object();
        $o3 = new Kint_Object();

        $a = array($o1, $o2, $o3);

        $o1->access = Kint_Object::ACCESS_PRIVATE;
        $o2->access = Kint_Object::ACCESS_PROTECTED;
        $o3->access = Kint_Object::ACCESS_PUBLIC;

        $this->assertSame(array($o1, $o2, $o3), $a);

        usort($a, 'Kint_Object::sortByAccess');
        $this->assertSame(array($o3, $o2, $o1), $a);

        $o1->access = Kint_Object::ACCESS_PROTECTED;
        $o2->access = Kint_Object::ACCESS_PRIVATE;

        usort($a, 'Kint_Object::sortByAccess');
        $this->assertSame(array($o3, $o1, $o2), $a);
    }

    public function testSortByName()
    {
        $o1 = new Kint_Object();
        $o2 = new Kint_Object();
        $o3 = new Kint_Object();

        $a = array($o1, $o2, $o3);

        $o1->name = 'Name Z';
        $o2->name = 'Name B';
        $o3->name = 'Name A';

        $this->assertSame(array($o1, $o2, $o3), $a);

        usort($a, 'Kint_Object::sortByName');
        $this->assertSame(array($o3, $o2, $o1), $a);

        $o1->name = 'Name M';
        $o2->name = 'Name Z2';

        usort($a, 'Kint_Object::sortByName');
        $this->assertSame(array($o3, $o1, $o2), $a);
    }
}
