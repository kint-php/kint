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
use Kint\Test\Fixtures\Php81TestClass;
use Kint\Test\Fixtures\Php81TestEnum;
use Kint\Test\Fixtures\Php84ChildTestClass;
use Kint\Test\Fixtures\Php84TestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\Fixtures\TestInterface;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\ArrayValue;
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
     * @covers \Kint\Parser\ClassStaticsPlugin::getStatics
     * @covers \Kint\Parser\ClassStaticsPlugin::buildStaticValue
     */
    public function testParseStatics()
    {
        $p = new Parser(1);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('statics'));

        $p->addPlugin(new ClassStaticsPlugin($p));

        $expected = [
            [Php74ChildTestClass::class.'::$value_1', 'replaced'],
            [Php74ChildTestClass::class.'::$value_5', 5],
            [Php74ChildTestClass::class.'::$value_3', 'replaced'],
            [Php74ChildTestClass::class.'::$value_6', 6],
            [Php74TestClass::class.'::$value_2', 2],
            ($index_uninit = 5) => [Php74TestClass::class.'::$value_uninit', null],
            ($index_a_pub = 6) => [Php74TestClass::class.'::$value_a_pub', []],
            ($index_a_pro = 7) => [Php74TestClass::class.'::$value_a_pro', []],
            [Php74TestClass::class.'::$value_1', 1],
            [Php74TestClass::class.'::$value_3', 3],
            [Php74TestClass::class.'::$value_4', 4],
            ($index_a_pri = 11) => [Php74TestClass::class.'::$value_a_pri', []],
        ];

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');
        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertCount(\count($expected), $rep->getContents());

        foreach ($expected as $index => $expect) {
            $value = $rep->getContents()[$index];

            $this->assertInstanceOf(StaticPropertyContext::class, $value->getContext());
            $this->assertSame($expect[0], $value->getDisplayName());
            $this->assertFalse($value->getContext()->final);
            $this->assertNull($value->getContext()->getAccessPath());

            if ($index === $index_uninit) {
                $this->assertInstanceOf(UninitializedValue::class, $value);
            } elseif ([] === $expect[1]) {
                $this->assertInstanceOf(ArrayValue::class, $value);
                $this->assertEquals(true, $value->flags & AbstractValue::FLAG_DEPTH_LIMIT);
                $this->assertCount(0, $value->getContents());
            } else {
                $this->assertSame($expect[1], $value->getValue());
            }
        }

        $p->setDepthLimit(5);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');

        $this->assertEquals(false, $rep->getContents()[$index_a_pub]->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertSame(
            '\\'.Php74TestClass::class."::\$value_a_pub['contents']",
            $rep->getContents()[$index_a_pub]->getContents()['contents']->getContext()->getAccessPath()
        );
        $this->assertEquals(false, $rep->getContents()[$index_a_pro]->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull(
            $rep->getContents()[$index_a_pro]->getContents()['contents']->getContext()->getAccessPath()
        );
        $this->assertEquals(false, $rep->getContents()[$index_a_pri]->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertNull(
            $rep->getContents()[$index_a_pri]->getContents()['contents']->getContext()->getAccessPath()
        );

        $p->setCallerClass(Php74ChildTestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');
        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertCount(\count($expected), $rep->getContents());

        foreach ($expected as $index => $expect) {
            $value = $rep->getContents()[$index];

            $this->assertInstanceOf(StaticPropertyContext::class, $value->getContext());
            $this->assertSame($expect[0], $value->getDisplayName());
            $this->assertNull($value->getContext()->getAccessPath());
        }
        $this->assertSame(
            '\\'.Php74TestClass::class."::\$value_a_pub['contents']",
            $rep->getContents()[$index_a_pub]->getContents()['contents']->getContext()->getAccessPath()
        );
        $this->assertSame(
            '\\'.Php74TestClass::class."::\$value_a_pro['contents']",
            $rep->getContents()[$index_a_pro]->getContents()['contents']->getContext()->getAccessPath()
        );
        $this->assertNull(
            $rep->getContents()[$index_a_pri]->getContents()['contents']->getContext()->getAccessPath()
        );

        $p->setCallerClass(Php74TestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');
        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertCount(\count($expected), $rep->getContents());

        foreach ($expected as $index => $expect) {
            $value = $rep->getContents()[$index];

            $this->assertInstanceOf(StaticPropertyContext::class, $value->getContext());
            $this->assertSame($expect[0], $value->getDisplayName());
            $this->assertNull($value->getContext()->getAccessPath());
        }
        $this->assertSame(
            '\\'.Php74TestClass::class."::\$value_a_pub['contents']",
            $rep->getContents()[$index_a_pub]->getContents()['contents']->getContext()->getAccessPath()
        );
        $this->assertSame(
            '\\'.Php74TestClass::class."::\$value_a_pro['contents']",
            $rep->getContents()[$index_a_pro]->getContents()['contents']->getContext()->getAccessPath()
        );
        $this->assertSame(
            '\\'.Php74TestClass::class."::\$value_a_pri['contents']",
            $rep->getContents()[$index_a_pri]->getContents()['contents']->getContext()->getAccessPath()
        );
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parseComplete
     * @covers \Kint\Parser\ClassStaticsPlugin::getStatics
     * @covers \Kint\Parser\ClassStaticsPlugin::buildStaticValue
     */
    public function testParseFinalStatic()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing final static properties below PHP 8.4');
        }

        $p = new Parser(5);
        $p->addPlugin(new ClassStaticsPlugin($p));

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php84ChildTestClass();

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');

        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertSame(Php84TestClass::class.'::$l', $rep->getContents()[0]->getContext()->getName());
        $this->assertTrue($rep->getContents()[0]->getContext()->final);
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parseComplete
     * @covers \Kint\Parser\ClassStaticsPlugin::getCachedConstants
     * @covers \Kint\Parser\ClassStaticsPlugin::buildConstContext
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
            [Php74ChildTestClass::class.'::VALUE_1', 'replaced'],
            [Php74ChildTestClass::class.'::VALUE_5', 5],
            [Php74ChildTestClass::class.'::VALUE_3', 'replaced'],
            [Php74ChildTestClass::class.'::VALUE_6', 6],
            [Php74ChildTestClass::class.'::VALUE_ARRAY_2', []],
            [Php74TestClass::class.'::VALUE_2', 2],
            [Php74TestClass::class.'::VALUE_ARRAY', []],
            [Php74TestClass::class.'::VALUE_1', 1],
            [Php74TestClass::class.'::VALUE_3', 3],
            [Php74TestClass::class.'::VALUE_4', 4],
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
                $this->assertInstanceOf(ArrayValue::class, $value);
                $this->assertEquals(true, $value->flags & AbstractValue::FLAG_DEPTH_LIMIT);
                $this->assertCount(0, $value->getContents());
                $array_key ??= $index;
            } else {
                $this->assertSame($expect[1], $value->getValue());
            }
            $this->assertNull($value->getContext()->getAccessPath());
        }

        $p->setDepthLimit(0);
        $o = $p->parse($v, clone $b);
        $array_value = $o->getRepresentation('constants')->getContents()[$array_key];
        $this->assertEquals(false, $array_value->flags & AbstractValue::FLAG_DEPTH_LIMIT);

        $this->assertCount(1, $array_value->getContents());
        $this->assertSame(
            '\\'.Php74ChildTestClass::class."::VALUE_ARRAY_2['contents']",
            $array_value->getContents()['contents']->getContext()->getAccessPath()
        );
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parseComplete
     * @covers \Kint\Parser\ClassStaticsPlugin::getCachedConstants
     * @covers \Kint\Parser\ClassStaticsPlugin::buildConstContext
     */
    public function testParseEnumConstant()
    {
        if (!KINT_PHP81) {
            $this->markTestSkipped('Not testing final consts below PHP 8.1');
        }

        $p = new Parser(5);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = Php81TestEnum::Hearts;

        $o = $p->parse($v, clone $b);
        $this->assertNull($o->getRepresentation('constants'));

        $p->addPlugin(new ClassStaticsPlugin($p));

        $o = $p->parse($v, clone $b);
        $this->assertNull($o->getRepresentation('constants'));

        $v = Php81TestBackedEnum::Hearts;

        $o = $p->parse($v, clone $b);
        $this->assertNull($o->getRepresentation('constants'));
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parseComplete
     * @covers \Kint\Parser\ClassStaticsPlugin::getCachedConstants
     * @covers \Kint\Parser\ClassStaticsPlugin::buildConstContext
     */
    public function testParseInterfaceConst()
    {
        $p = new Parser(5);
        $p->addPlugin(new ClassStaticsPlugin($p));

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new TestClass();

        $o = $p->parse($v, clone $b);

        $rep = $o->getRepresentation('constants');

        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertSame(TestInterface::class.'::VALUE', $rep->getContents()[2]->getContext()->getName());
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parseComplete
     * @covers \Kint\Parser\ClassStaticsPlugin::getCachedConstants
     * @covers \Kint\Parser\ClassStaticsPlugin::buildConstContext
     */
    public function testParseFinalConst()
    {
        if (!KINT_PHP81) {
            $this->markTestSkipped('Not testing ClassStaticsPlugin on enums below PHP 8.1');
        }

        $p = new Parser(5);
        $p->addPlugin(new ClassStaticsPlugin($p));

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php81TestClass('');

        $o = $p->parse($v, clone $b);

        $rep = $o->getRepresentation('constants');

        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertSame(Php81TestClass::class.'::X', $rep->getContents()[0]->getContext()->getName());
        $this->assertTrue($rep->getContents()[0]->getContext()->final);
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

        $this->assertNull($b->getRepresentation('statics'));
        $this->assertNull($b->getRepresentation('constants'));
    }
}
