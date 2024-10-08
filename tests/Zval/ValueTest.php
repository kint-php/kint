<?php

declare(strict_types=1);

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

use Kint\Test\KintTestCase;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

/**
 * @coversNothing
 */
class ValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Zval\Value::__construct
     */
    public function testConstruct()
    {
        $o = new Value('name goes here');
        $this->assertSame('name goes here', $o->name);

        $o = new Value(1234);
        $this->assertSame(1234, $o->name);
    }

    /**
     * @covers \Kint\Zval\Value::addRepresentation
     * @covers \Kint\Zval\Value::getRepresentations
     */
    public function testAddRepresentation()
    {
        $o = new Value('base');

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
        $o = new Value('base');
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
        $o = new Value('base');
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
        $o = new Value('base');
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
        $o = new Value('base');
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
        $o = new Value('base');
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
        $o = new Value('base');
        $o->type = 'array';
        $this->assertSame('array', $o->getType());
    }

    public function modifierProvider()
    {
        return [
            'public' => [
                false,
                false,
                false,
                Value::ACCESS_PUBLIC,
                Value::ACCESS_NONE,
                'public',
            ],
            'public const' => [
                true,
                false,
                false,
                Value::ACCESS_PUBLIC,
                Value::ACCESS_NONE,
                'public const',
            ],
            'public static' => [
                false,
                true,
                false,
                Value::ACCESS_PUBLIC,
                Value::ACCESS_NONE,
                'public static',
            ],
            'protected' => [
                false,
                false,
                false,
                Value::ACCESS_PROTECTED,
                Value::ACCESS_NONE,
                'protected',
            ],
            'private' => [
                false,
                false,
                false,
                Value::ACCESS_PRIVATE,
                Value::ACCESS_NONE,
                'private',
            ],
            'none' => [
                false,
                false,
                false,
                Value::ACCESS_NONE,
                Value::ACCESS_NONE,
                null,
            ],
            'private static' => [
                false,
                true,
                false,
                Value::ACCESS_PRIVATE,
                Value::ACCESS_NONE,
                'private static',
            ],
            'public const static' => [
                true,
                true,
                false,
                Value::ACCESS_PUBLIC,
                Value::ACCESS_NONE,
                'public const static',
            ],
            'const' => [
                true,
                false,
                false,
                Value::ACCESS_NONE,
                Value::ACCESS_NONE,
                'const',
            ],
            'public readonly' => [
                false,
                false,
                true,
                Value::ACCESS_PUBLIC,
                Value::ACCESS_NONE,
                'public readonly',
            ],
            'private readonly' => [
                false,
                false,
                true,
                Value::ACCESS_PRIVATE,
                Value::ACCESS_NONE,
                'private readonly',
            ],
            'private(set) set hook' => [
                false,
                false,
                false,
                Value::ACCESS_PUBLIC,
                Value::ACCESS_PRIVATE,
                'private(set)',
            ],
            'protected(set) set hook' => [
                false,
                false,
                false,
                Value::ACCESS_PUBLIC,
                Value::ACCESS_PROTECTED,
                'protected(set)',
            ],
            'protected private(set) set hook' => [
                false,
                false,
                false,
                Value::ACCESS_PROTECTED,
                Value::ACCESS_PRIVATE,
                'protected private(set)',
            ],
        ];
    }

    /**
     * @dataProvider modifierProvider
     *
     * @covers \Kint\Zval\Value::getModifiers
     */
    public function testGetModifiers(bool $const, bool $static, bool $readonly, int $access, int $access_set, ?string $expect)
    {
        $o = new Value('base');
        $o->readonly = $readonly;
        $o->const = $const;
        $o->static = $static;
        $o->access = $access;
        $o->access_set = $access_set;
        $this->assertSame($expect, $o->getModifiers());
    }

    /**
     * @covers \Kint\Zval\Value::getAccess
     */
    public function testGetAccess()
    {
        $o = new Value('base');
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
        $o = new Value('$var');
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
        $o = new Value('base');
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
        $o = new Value('base');
        $this->assertNull($o->getSize());
        $o->size = 0;
        $this->assertSame('0', $o->getSize());
        $o->size = 42;
        $this->assertSame('42', $o->getSize());
    }

    /**
     * @covers \Kint\Zval\Value::getValueShort
     */
    public function testGetValueShort()
    {
        $o = new Value('base');
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
        $this->assertSame('1234', $o->getValueShort());
        $o->type = 'double';
        $r->contents = 1234.5678;
        $this->assertSame('1234.5678', $o->getValueShort());
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
        $o = new Value('base');
        $this->assertNull($o->getAccessPath());
        $o->access_path = 'abcdefg, hijk elemeno p';
        $this->assertSame('abcdefg, hijk elemeno p', $o->getAccessPath());
    }

    /**
     * @covers \Kint\Zval\Value::getHooks
     */
    public function testGetHooks()
    {
        $o = new Value('base');
        $o->hook_set_type = 'KintTestTypeGoesHere';
        $this->assertNull($o->getHooks());

        $o->hooks = Value::HOOK_GET;
        $this->assertSame('{ get; }', $o->getHooks());
        $o->hooks = Value::HOOK_GET | Value::HOOK_GET_REF;
        $this->assertSame('{ &get; }', $o->getHooks());
        $o->hooks = Value::HOOK_SET;
        $this->assertSame('{ set; }', $o->getHooks());
        $o->hooks = Value::HOOK_SET | Value::HOOK_GET;
        $this->assertSame('{ get; set; }', $o->getHooks());
        $o->hooks = Value::HOOK_SET | Value::HOOK_GET | Value::HOOK_GET_REF;
        $this->assertSame('{ &get; set; }', $o->getHooks());

        $o->hooks = Value::HOOK_SET | Value::HOOK_SET_TYPE;
        $this->assertSame('{ set(KintTestTypeGoesHere); }', $o->getHooks());
        $o->hook_set_type = null;
        $this->assertSame('{ set; }', $o->getHooks());
    }

    /**
     * @covers \Kint\Zval\Value::transplant
     */
    public function testTransplant()
    {
        $o = new Value('name');
        $o->type = 'type';
        $o->readonly = true;
        $o->static = true;
        $o->const = true;
        $o->access = Value::ACCESS_PUBLIC;
        $o->owner_class = 'owner_class';
        $o->access_path = 'access_path';
        $o->operator = Value::OPERATOR_OBJECT;
        $o->reference = true;
        $o->virtual = true;
        $o->hooks = Value::HOOK_GET | Value::HOOK_SET | Value::HOOK_SET_TYPE;
        $o->hook_set_type = 'int';
        $o->depth = 43;
        $o->size = 42;
        $o->hints = [
            'test' => true,
            'transplant' => true,
            'hints' => true,
        ];
        $o->value = new Representation('Test value');

        $r = new Representation('Test');
        $o->addRepresentation($r);

        $o2 = new Value('base');
        $o2->transplant($o);

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
        $this->assertSame($o->value, $o2->value);

        $o2 = new Value('base');
        $r2 = new Representation('Test 2');
        $o2->addRepresentation($r2);
        $o2->hints = [
            'test' => true,
            'thoroughly' => true,
        ];

        $o2->transplant($o);

        $this->assertSame(['test_2' => $r2, 'test' => $r], $o2->getRepresentations());
        $this->assertSame([
            'test' => true,
            'thoroughly' => true,
            'transplant' => true,
            'hints' => true,
        ], $o2->hints);
    }

    /**
     * @covers \Kint\Zval\Value::sortByAccess
     */
    public function testSortByAccess()
    {
        $o1 = new Value('base');
        $o2 = new Value('base');
        $o3 = new Value('base');

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
        $o1 = new Value('Name Z');
        $o2 = new Value('Name B');
        $o3 = new Value('Name A');

        $a = [$o1, $o2, $o3];

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
