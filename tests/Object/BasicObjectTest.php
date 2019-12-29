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
use Kint\Object\Representation\Representation;

class BasicObjectTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \Kint\Object\BasicObject::__construct
     * @covers \Kint\Object\BasicObject::addRepresentation
     * @covers \Kint\Object\BasicObject::getRepresentations
     */
    public function testAddRepresentation()
    {
        $o = new BasicObject();

        $this->assertSame([], $o->getRepresentations());
        $this->assertNull($o->value);

        $this->assertTrue($o->addRepresentation($r1 = new Representation('Rep 1')));
        $this->assertSame(
            [
                'rep_1' => $r1,
            ],
            $o->getRepresentations()
        );
        $this->assertNull($o->value);

        $this->assertFalse($o->addRepresentation($r1));
        $this->assertSame(
            [
                'rep_1' => $r1,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r2 = new Representation('Rep 2')));
        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r3 = new Representation('Rep 3'), 0));
        $this->assertSame(
            [
                'rep_3' => $r3,
                'rep_1' => $r1,
                'rep_2' => $r2,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r4 = new Representation('Rep 4'), 1));
        $this->assertSame(
            [
                'rep_3' => $r3,
                'rep_4' => $r4,
                'rep_1' => $r1,
                'rep_2' => $r2,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r5 = new Representation('Rep 5'), 100));
        $this->assertSame(
            [
                'rep_3' => $r3,
                'rep_4' => $r4,
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_5' => $r5,
            ],
            $o->getRepresentations()
        );

        $this->assertTrue($o->addRepresentation($r6 = new Representation('Rep 6'), -100));
        $this->assertSame(
            [
                'rep_6' => $r6,
                'rep_3' => $r3,
                'rep_4' => $r4,
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_5' => $r5,
            ],
            $o->getRepresentations()
        );

        $this->assertNull($o->value);
    }

    /**
     * @covers \Kint\Object\BasicObject::replaceRepresentation
     */
    public function testReplaceRepresentation()
    {
        $o = new BasicObject();
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation($r2 = new Representation('Rep 2'));
        $o->addRepresentation($r3 = new Representation('Rep 3'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->replaceRepresentation($r2_2 = new Representation('Rep 2'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2_2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->replaceRepresentation($r2, 0);

        $this->assertSame(
            [
                'rep_2' => $r2,
                'rep_1' => $r1,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->replaceRepresentation($r2_2, 1);

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2_2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );
    }

    /**
     * @covers \Kint\Object\BasicObject::removeRepresentation
     */
    public function testRemoveRepresentation()
    {
        $o = new BasicObject();
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation($r2 = new Representation('Rep 2'));
        $o->addRepresentation($r3 = new Representation('Rep 3'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->removeRepresentation('rep_2');

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );

        $o->removeRepresentation($r1);
        $this->assertSame(['rep_3' => $r3], $o->getRepresentations());
    }

    /**
     * @covers \Kint\Object\BasicObject::getRepresentation
     */
    public function testGetRepresentation()
    {
        $o = new BasicObject();
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation($r2 = new Representation('Rep 2'));
        $o->addRepresentation($r3 = new Representation('Rep 3'));

        $this->assertSame($r1, $o->getRepresentation('rep_1'));
        $this->assertSame($r2, $o->getRepresentation('rep_2'));
        $this->assertSame($r3, $o->getRepresentation('rep_3'));
        $this->assertNull($o->getRepresentation('Non-existant representation name'));
    }

    /**
     * @covers \Kint\Object\BasicObject::getRepresentations
     */
    public function testGetRepresentations()
    {
        $o = new BasicObject();
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation($r2 = new Representation('Rep 2'));
        $o->addRepresentation($r3 = new Representation('Rep 3'));

        $this->assertSame(
            [
                'rep_1' => $r1,
                'rep_2' => $r2,
                'rep_3' => $r3,
            ],
            $o->getRepresentations()
        );
    }

    /**
     * @covers \Kint\Object\BasicObject::clearRepresentations
     */
    public function testClearRepresentations()
    {
        $o = new BasicObject();
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation(new Representation('Rep 2'));
        $o->addRepresentation(new Representation('Rep 3'));
        $o->value = $r1;

        $o->clearRepresentations();

        $this->assertSame([], $o->getRepresentations());
        $this->assertSame($r1, $o->value);
    }

    /**
     * @covers \Kint\Object\BasicObject::getType
     */
    public function testGetType()
    {
        $o = new BasicObject();
        $o->type = 'array';
        $this->assertSame('array', $o->getType());
    }

    public function modifierProvider()
    {
        return [
            'public' => [
                false,
                false,
                BasicObject::ACCESS_PUBLIC,
                'public',
            ],
            'public const' => [
                true,
                false,
                BasicObject::ACCESS_PUBLIC,
                'public const',
            ],
            'public static' => [
                false,
                true,
                BasicObject::ACCESS_PUBLIC,
                'public static',
            ],
            'protected' => [
                false,
                false,
                BasicObject::ACCESS_PROTECTED,
                'protected',
            ],
            'private' => [
                false,
                false,
                BasicObject::ACCESS_PRIVATE,
                'private',
            ],
            'none' => [
                false,
                false,
                BasicObject::ACCESS_NONE,
                null,
            ],
            'private static' => [
                false,
                true,
                BasicObject::ACCESS_PRIVATE,
                'private static',
            ],
            'public const static' => [
                true,
                true,
                BasicObject::ACCESS_PUBLIC,
                'public const static',
            ],
            'const' => [
                true,
                false,
                BasicObject::ACCESS_NONE,
                'const',
            ],
        ];
    }

    /**
     * @dataProvider modifierProvider
     * @covers \Kint\Object\BasicObject::getModifiers
     *
     * @param bool        $const
     * @param bool        $static
     * @param bool        $access
     * @param null|string $expect
     */
    public function testGetModifiers($const, $static, $access, $expect)
    {
        $o = new BasicObject();
        $o->const = $const;
        $o->static = $static;
        $o->access = $access;
        $this->assertSame($expect, $o->getModifiers());
    }

    /**
     * @covers \Kint\Object\BasicObject::getAccess
     */
    public function testGetAccess()
    {
        $o = new BasicObject();
        $this->assertNull($o->getAccess());
        $o->access = BasicObject::ACCESS_PUBLIC;
        $this->assertSame('public', $o->getAccess());
        $o->access = BasicObject::ACCESS_PROTECTED;
        $this->assertSame('protected', $o->getAccess());
        $o->access = BasicObject::ACCESS_PRIVATE;
        $this->assertSame('private', $o->getAccess());
    }

    /**
     * @covers \Kint\Object\BasicObject::getName
     */
    public function testGetName()
    {
        $o = new BasicObject();
        $o->name = '$var';
        $this->assertSame('$var', $o->getName());
        $o->name = '($a + $b)';
        $this->assertSame('($a + $b)', $o->getName());
        $o->name = 'This is just a name, nothing more, nothing less.';
        $this->assertSame('This is just a name, nothing more, nothing less.', $o->getName());
    }

    /**
     * @covers \Kint\Object\BasicObject::getOperator
     */
    public function testGetOperator()
    {
        $o = new BasicObject();
        $this->assertNull($o->getOperator());
        $o->operator = BasicObject::OPERATOR_NONE;
        $this->assertNull($o->getOperator());
        $o->operator = BasicObject::OPERATOR_ARRAY;
        $this->assertSame('=>', $o->getOperator());
        $o->operator = BasicObject::OPERATOR_OBJECT;
        $this->assertSame('->', $o->getOperator());
        $o->operator = BasicObject::OPERATOR_STATIC;
        $this->assertSame('::', $o->getOperator());
    }

    /**
     * @covers \Kint\Object\BasicObject::getSize
     */
    public function testGetSize()
    {
        $o = new BasicObject();
        $this->assertNull($o->getSize());
        $o->size = 0;
        $this->assertSame(0, $o->getSize());
        $o->size = 42;
        $this->assertSame(42, $o->getSize());
    }

    /**
     * @covers \Kint\Object\BasicObject::getValueShort
     */
    public function testGetValueShort()
    {
        $o = new BasicObject();
        $r = new Representation('contents');
        $this->assertNull($o->getValueShort());
        $o->value = $r;

        $r->contents = true;
        $this->assertNull($o->getValueShort());
        $o->type = 'boolean';
        $this->assertSame('true', $o->getValueShort());
        $r->contents = false;
        $this->assertSame('false', $o->getValueShort());
        $o->type = 'integer';
        $r->contents = 1234;
        $this->assertSame(1234, $o->getValueShort());
        $o->type = 'double';
        $r->contents = 1234.5678;
        $this->assertSame(1234.5678, $o->getValueShort());
        $o->type = 'array';
        $r->contents = [];
        $this->assertNull($o->getValueShort());
        $o->type = 'string';
        $r->contents = 'string';
        $this->assertNull($o->getValueShort());
    }

    /**
     * @covers \Kint\Object\BasicObject::getAccessPath
     */
    public function testGetAccessPath()
    {
        $o = new BasicObject();
        $this->assertNull($o->getAccessPath());
        $o->access_path = 'abcdefg, hijk elemeno p';
        $this->assertSame('abcdefg, hijk elemeno p', $o->getAccessPath());
    }

    /**
     * @covers \Kint\Object\BasicObject::blank
     */
    public function testBlank()
    {
        $o = new BasicObject();
        $this->assertNull($o->name);
        $this->assertNull($o->access_path);

        $o = BasicObject::blank();
        $this->assertNull($o->name);
        $this->assertNull($o->access_path);

        $o = BasicObject::blank('$var');
        $this->assertSame('$var', $o->name);
        $this->assertNull($o->access_path);

        $o = BasicObject::blank('Name', 'access_path');
        $this->assertSame('Name', $o->name);
        $this->assertSame('access_path', $o->access_path);
    }

    /**
     * @covers \Kint\Object\BasicObject::transplant
     */
    public function testTransplant()
    {
        $o = new BasicObject();

        $o->name = 'name';
        $o->size = 42;
        $o->access_path = 'access_path';
        $o->access = BasicObject::ACCESS_PUBLIC;
        $o->static = true;
        $o->const = true;
        $o->type = 'type';
        $o->depth = 43;
        $o->owner_class = 'owner_class';
        $o->operator = BasicObject::OPERATOR_OBJECT;
        $o->reference = true;
        $o->hints = ['test', 'transplant', 'hints'];

        $r = new Representation('Test');
        $o->addRepresentation($r);

        $o2 = new BasicObject();
        $o2->transplant($o);

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
        $this->assertSame($o->value, $o2->value);

        $o2 = new BasicObject();
        $r2 = new Representation('Test 2');
        $o2->addRepresentation($r2);
        $o2->hints = ['test', 'thoroughly'];

        $o2->transplant($o);

        $this->assertSame(['test_2' => $r2, 'test' => $r], $o2->getRepresentations());
        $this->assertSame(['test', 'thoroughly', 'test', 'transplant', 'hints'], $o2->hints);
    }

    /**
     * @covers \Kint\Object\BasicObject::sortByAccess
     */
    public function testSortByAccess()
    {
        $o1 = new BasicObject();
        $o2 = new BasicObject();
        $o3 = new BasicObject();

        $a = [$o1, $o2, $o3];

        $o1->access = BasicObject::ACCESS_PRIVATE;
        $o2->access = BasicObject::ACCESS_PROTECTED;
        $o3->access = BasicObject::ACCESS_PUBLIC;

        $this->assertSame([$o1, $o2, $o3], $a);

        \usort($a, 'Kint\\Object\\BasicObject::sortByAccess');
        $this->assertSame([$o3, $o2, $o1], $a);

        $o1->access = BasicObject::ACCESS_PROTECTED;
        $o2->access = BasicObject::ACCESS_PRIVATE;

        \usort($a, 'Kint\\Object\\BasicObject::sortByAccess');
        $this->assertSame([$o3, $o1, $o2], $a);
    }

    /**
     * @covers \Kint\Object\BasicObject::sortByName
     */
    public function testSortByName()
    {
        $o1 = new BasicObject();
        $o2 = new BasicObject();
        $o3 = new BasicObject();

        $a = [$o1, $o2, $o3];

        $o1->name = 'Name Z';
        $o2->name = 'Name B';
        $o3->name = 'Name A';

        $this->assertSame([$o1, $o2, $o3], $a);

        \usort($a, 'Kint\\Object\\BasicObject::sortByName');
        $this->assertSame([$o3, $o2, $o1], $a);

        $o1->name = 'Name M';
        $o2->name = 'Name Z2';

        \usort($a, 'Kint\\Object\\BasicObject::sortByName');
        $this->assertSame([$o3, $o1, $o2], $a);

        $o1->name = '123';
        $o2->name = 123;

        \usort($a, 'Kint\\Object\\BasicObject::sortByName');
        $this->assertSame([$o2, $o1, $o3], $a);
    }
}
