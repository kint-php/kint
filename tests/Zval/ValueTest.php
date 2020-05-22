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

namespace Kint\Test\Zval;

use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

class ValueTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \Kint\Zval\Value::__construct
     * @covers \Kint\Zval\Value::addRepresentation
     * @covers \Kint\Zval\Value::getRepresentations
     */
    public function testAddRepresentation()
    {
        $o = new Value();

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
     * @covers \Kint\Zval\Value::replaceRepresentation
     */
    public function testReplaceRepresentation()
    {
        $o = new Value();
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
     * @covers \Kint\Zval\Value::removeRepresentation
     */
    public function testRemoveRepresentation()
    {
        $o = new Value();
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
     * @covers \Kint\Zval\Value::getRepresentation
     */
    public function testGetRepresentation()
    {
        $o = new Value();
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation($r2 = new Representation('Rep 2'));
        $o->addRepresentation($r3 = new Representation('Rep 3'));

        $this->assertSame($r1, $o->getRepresentation('rep_1'));
        $this->assertSame($r2, $o->getRepresentation('rep_2'));
        $this->assertSame($r3, $o->getRepresentation('rep_3'));
        $this->assertNull($o->getRepresentation('Non-existant representation name'));
    }

    /**
     * @covers \Kint\Zval\Value::getRepresentations
     */
    public function testGetRepresentations()
    {
        $o = new Value();
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
     * @covers \Kint\Zval\Value::clearRepresentations
     */
    public function testClearRepresentations()
    {
        $o = new Value();
        $o->addRepresentation($r1 = new Representation('Rep 1'));
        $o->addRepresentation(new Representation('Rep 2'));
        $o->addRepresentation(new Representation('Rep 3'));
        $o->value = $r1;

        $o->clearRepresentations();

        $this->assertSame([], $o->getRepresentations());
        $this->assertSame($r1, $o->value);
    }

    /**
     * @covers \Kint\Zval\Value::getType
     */
    public function testGetType()
    {
        $o = new Value();
        $o->type = 'array';
        $this->assertSame('array', $o->getType());
    }

    public function modifierProvider()
    {
        return [
            'public' => [
                false,
                false,
                Value::ACCESS_PUBLIC,
                'public',
            ],
            'public const' => [
                true,
                false,
                Value::ACCESS_PUBLIC,
                'public const',
            ],
            'public static' => [
                false,
                true,
                Value::ACCESS_PUBLIC,
                'public static',
            ],
            'protected' => [
                false,
                false,
                Value::ACCESS_PROTECTED,
                'protected',
            ],
            'private' => [
                false,
                false,
                Value::ACCESS_PRIVATE,
                'private',
            ],
            'none' => [
                false,
                false,
                Value::ACCESS_NONE,
                null,
            ],
            'private static' => [
                false,
                true,
                Value::ACCESS_PRIVATE,
                'private static',
            ],
            'public const static' => [
                true,
                true,
                Value::ACCESS_PUBLIC,
                'public const static',
            ],
            'const' => [
                true,
                false,
                Value::ACCESS_NONE,
                'const',
            ],
        ];
    }

    /**
     * @dataProvider modifierProvider
     * @covers \Kint\Zval\Value::getModifiers
     *
     * @param bool        $const
     * @param bool        $static
     * @param bool        $access
     * @param null|string $expect
     */
    public function testGetModifiers($const, $static, $access, $expect)
    {
        $o = new Value();
        $o->const = $const;
        $o->static = $static;
        $o->access = $access;
        $this->assertSame($expect, $o->getModifiers());
    }

    /**
     * @covers \Kint\Zval\Value::getAccess
     */
    public function testGetAccess()
    {
        $o = new Value();
        $this->assertNull($o->getAccess());
        $o->access = Value::ACCESS_PUBLIC;
        $this->assertSame('public', $o->getAccess());
        $o->access = Value::ACCESS_PROTECTED;
        $this->assertSame('protected', $o->getAccess());
        $o->access = Value::ACCESS_PRIVATE;
        $this->assertSame('private', $o->getAccess());
    }

    /**
     * @covers \Kint\Zval\Value::getName
     */
    public function testGetName()
    {
        $o = new Value();
        $o->name = '$var';
        $this->assertSame('$var', $o->getName());
        $o->name = '($a + $b)';
        $this->assertSame('($a + $b)', $o->getName());
        $o->name = 'This is just a name, nothing more, nothing less.';
        $this->assertSame('This is just a name, nothing more, nothing less.', $o->getName());
    }

    /**
     * @covers \Kint\Zval\Value::getOperator
     */
    public function testGetOperator()
    {
        $o = new Value();
        $this->assertNull($o->getOperator());
        $o->operator = Value::OPERATOR_NONE;
        $this->assertNull($o->getOperator());
        $o->operator = Value::OPERATOR_ARRAY;
        $this->assertSame('=>', $o->getOperator());
        $o->operator = Value::OPERATOR_OBJECT;
        $this->assertSame('->', $o->getOperator());
        $o->operator = Value::OPERATOR_STATIC;
        $this->assertSame('::', $o->getOperator());
    }

    /**
     * @covers \Kint\Zval\Value::getSize
     */
    public function testGetSize()
    {
        $o = new Value();
        $this->assertNull($o->getSize());
        $o->size = 0;
        $this->assertSame(0, $o->getSize());
        $o->size = 42;
        $this->assertSame(42, $o->getSize());
    }

    /**
     * @covers \Kint\Zval\Value::getValueShort
     */
    public function testGetValueShort()
    {
        $o = new Value();
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
     * @covers \Kint\Zval\Value::getAccessPath
     */
    public function testGetAccessPath()
    {
        $o = new Value();
        $this->assertNull($o->getAccessPath());
        $o->access_path = 'abcdefg, hijk elemeno p';
        $this->assertSame('abcdefg, hijk elemeno p', $o->getAccessPath());
    }

    /**
     * @covers \Kint\Zval\Value::blank
     */
    public function testBlank()
    {
        $o = new Value();
        $this->assertNull($o->name);
        $this->assertNull($o->access_path);

        $o = Value::blank();
        $this->assertNull($o->name);
        $this->assertNull($o->access_path);

        $o = Value::blank('$var');
        $this->assertSame('$var', $o->name);
        $this->assertNull($o->access_path);

        $o = Value::blank('Name', 'access_path');
        $this->assertSame('Name', $o->name);
        $this->assertSame('access_path', $o->access_path);
    }

    /**
     * @covers \Kint\Zval\Value::transplant
     */
    public function testTransplant()
    {
        $o = new Value();

        $o->name = 'name';
        $o->size = 42;
        $o->access_path = 'access_path';
        $o->access = Value::ACCESS_PUBLIC;
        $o->static = true;
        $o->const = true;
        $o->type = 'type';
        $o->depth = 43;
        $o->owner_class = 'owner_class';
        $o->operator = Value::OPERATOR_OBJECT;
        $o->reference = true;
        $o->hints = ['test', 'transplant', 'hints'];

        $r = new Representation('Test');
        $o->addRepresentation($r);

        $o2 = new Value();
        $o2->transplant($o);

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
        $this->assertSame($o->value, $o2->value);

        $o2 = new Value();
        $r2 = new Representation('Test 2');
        $o2->addRepresentation($r2);
        $o2->hints = ['test', 'thoroughly'];

        $o2->transplant($o);

        $this->assertSame(['test_2' => $r2, 'test' => $r], $o2->getRepresentations());
        $this->assertSame(['test', 'thoroughly', 'test', 'transplant', 'hints'], $o2->hints);
    }

    /**
     * @covers \Kint\Zval\Value::sortByAccess
     */
    public function testSortByAccess()
    {
        $o1 = new Value();
        $o2 = new Value();
        $o3 = new Value();

        $a = [$o1, $o2, $o3];

        $o1->access = Value::ACCESS_PRIVATE;
        $o2->access = Value::ACCESS_PROTECTED;
        $o3->access = Value::ACCESS_PUBLIC;

        $this->assertSame([$o1, $o2, $o3], $a);

        \usort($a, 'Kint\\Zval\\Value::sortByAccess');
        $this->assertSame([$o3, $o2, $o1], $a);

        $o1->access = Value::ACCESS_PROTECTED;
        $o2->access = Value::ACCESS_PRIVATE;

        \usort($a, 'Kint\\Zval\\Value::sortByAccess');
        $this->assertSame([$o3, $o1, $o2], $a);
    }

    /**
     * @covers \Kint\Zval\Value::sortByName
     */
    public function testSortByName()
    {
        $o1 = new Value();
        $o2 = new Value();
        $o3 = new Value();

        $a = [$o1, $o2, $o3];

        $o1->name = 'Name Z';
        $o2->name = 'Name B';
        $o3->name = 'Name A';

        $this->assertSame([$o1, $o2, $o3], $a);

        \usort($a, 'Kint\\Zval\\Value::sortByName');
        $this->assertSame([$o3, $o2, $o1], $a);

        $o1->name = 'Name M';
        $o2->name = 'Name Z2';

        \usort($a, 'Kint\\Zval\\Value::sortByName');
        $this->assertSame([$o3, $o1, $o2], $a);

        $o1->name = '123';
        $o2->name = 123;

        \usort($a, 'Kint\\Zval\\Value::sortByName');
        $this->assertSame([$o2, $o1, $o3], $a);
    }
}
