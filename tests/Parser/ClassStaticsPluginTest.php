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

namespace Kint\Test\Parser;

use Kint\Parser\ClassStaticsPlugin;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\Php74ChildTestClass;
use Kint\Test\Fixtures\Php74TestClass;
use Kint\Test\Fixtures\Php81TestBackedEnum;
use Kint\Test\Fixtures\Php81TestEnum;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ClassConstContext;
use Kint\Value\Context\StaticPropertyContext;
use Kint\Value\FixedWidthValue;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\UninitializedValue;

/**
 * @coversNothing
 */
class ClassStaticsPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::getTriggers
     * @covers \Kint\Parser\ClassStaticsPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new ClassStaticsPlugin($this->createStub(Parser::class));

        $this->assertSame(['object'], $p->getTypes());
        $this->assertSame(Parser::TRIGGER_SUCCESS, $p->getTriggers());
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parseComplete
     */
    public function testParseStatics()
    {
        $p = new Parser(5);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('statics'));

        $p->addPlugin(new ClassStaticsPlugin($p));

        $expected = [
            ['$value_1', 'replaced', '\\'.Php74ChildTestClass::class.'::$value_1'],
            ['$value_5', 5, '\\'.Php74ChildTestClass::class.'::$value_5'],
            ['$value_3', 'replaced', null],
            ['$value_6', 6, null],
            ['$value_2', 2, '\\'.Php74TestClass::class.'::$value_2'],
            ['$value_uninit', null, null],
            [Php74TestClass::class.'::$value_1', 1, '\\'.Php74TestClass::class.'::$value_1'],
            [Php74TestClass::class.'::$value_3', 3, null],
            [Php74TestClass::class.'::$value_4', 4, null],
        ];

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');
        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertCount(\count($expected), $rep->getContents());

        foreach ($expected as $index => $expect) {
            $value = $rep->getContents()[$index];

            $this->assertInstanceOf(StaticPropertyContext::class, $value->getContext());
            $this->assertSame($expect[0], $value->getDisplayName());
            $this->assertSame($expect[2], $value->getContext()->getAccessPath());

            if ('$value_uninit' === $expect[0]) {
                $this->assertInstanceOf(UninitializedValue::class, $value);
            } else {
                $this->assertSame($expect[1], $value->getValue());
            }
        }

        $expected_copy = $expected;
        $expected_copy[2][2] = '\\'.Php74ChildTestClass::class.'::$value_3';
        $expected_copy[3][2] = '\\'.Php74ChildTestClass::class.'::$value_6';
        $expected_copy[5][2] = '\\'.Php74TestClass::class.'::$value_uninit';

        $p->setCallerClass(Php74ChildTestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');
        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertCount(\count($expected), $rep->getContents());

        foreach ($expected_copy as $index => $expect) {
            $this->assertSame($expect[2], $rep->getContents()[$index]->getContext()->getAccessPath());
        }

        $expected_copy = $expected;
        $expected_copy[5][2] = '\\'.Php74TestClass::class.'::$value_uninit';
        $expected_copy[7][2] = '\\'.Php74TestClass::class.'::$value_3';
        $expected_copy[8][2] = '\\'.Php74TestClass::class.'::$value_4';

        $p->setCallerClass(Php74TestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');
        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertCount(\count($expected), $rep->getContents());

        foreach ($expected_copy as $index => $expect) {
            $this->assertSame($expect[2], $rep->getContents()[$index]->getContext()->getAccessPath());
        }
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parseComplete
     * @covers \Kint\Parser\ClassStaticsPlugin::getCachedConstants
     */
    public function testParseConstants()
    {
        $p = new Parser(5);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('constants'));

        $p->addPlugin(new ClassStaticsPlugin($p));

        $expected = [
            ['VALUE_1', 'replaced', '\\'.Php74ChildTestClass::class.'::VALUE_1'],
            ['VALUE_5', 5, '\\'.Php74ChildTestClass::class.'::VALUE_5'],
            ['VALUE_3', 'replaced', null],
            ['VALUE_6', 6, null],
            ['VALUE_2', 2, '\\'.Php74TestClass::class.'::VALUE_2'],
            ['VALUE_ARRAY', [], null],
            [Php74TestClass::class.'::VALUE_1', 1, '\\'.Php74TestClass::class.'::VALUE_1'],
            [Php74TestClass::class.'::VALUE_3', 3, null],
            [Php74TestClass::class.'::VALUE_4', 4, null],
        ];

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('constants');
        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertCount(\count($expected), $rep->getContents());

        $array_key = null;

        foreach ($expected as $index => $expect) {
            $value = $rep->getContents()[$index];

            $this->assertInstanceOf(ClassConstContext::class, $value->getContext());
            $this->assertSame($expect[0], $value->getDisplayName());
            if ([] === $expect[1]) {
                $this->assertEquals(true, $value->flags & AbstractValue::FLAG_DEPTH_LIMIT);
                $array_key = $index;
            } else {
                $this->assertSame($expect[1], $value->getValue());
            }
            $this->assertSame($expect[2], $value->getContext()->getAccessPath());
        }

        $p->setDepthLimit(0);
        $o = $p->parse($v, clone $b);
        $this->assertEquals(false, $o->getRepresentation('constants')->getContents()[$array_key]->flags & AbstractValue::FLAG_DEPTH_LIMIT);
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parseComplete
     * @covers \Kint\Parser\ClassStaticsPlugin::getCachedConstants
     */
    public function testParseEnumConstant()
    {
        if (!KINT_PHP81) {
            $this->markTestSkipped('Not testing ClassStaticsPlugin on enums below PHP 8.1');
        }

        $p = new Parser(5);
        $p->addPlugin(new ClassStaticsPlugin($p));

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = Php81TestEnum::Hearts;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('statics'));

        $v = Php81TestBackedEnum::Hearts;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('statics'));
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parseComplete
     */
    public function testParseBadValue()
    {
        $p = new Parser(5);
        $csp = new ClassStaticsPlugin($p);

        $v = null;
        $b = new FixedWidthValue(new BaseContext('$v'), $v);
        $csp->parseComplete($v, $b, Parser::TRIGGER_SUCCESS);

        $this->assertNull($b->getRepresentation('methods'));
    }
}
