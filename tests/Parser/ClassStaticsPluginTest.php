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
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

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
     * @covers \Kint\Parser\ClassStaticsPlugin::parse
     */
    public function testParseStatics()
    {
        $p = new Parser(5);

        $b = new Value('$v');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('statics'));

        $p->addPlugin(new ClassStaticsPlugin($p));

        $expected = [
            ['$value_1', false, true, 'replaced', '\\'.Php74ChildTestClass::class.'::$value_1'],
            ['$value_5', false, true, 5, '\\'.Php74ChildTestClass::class.'::$value_5'],
            ['$value_3', false, true, 'replaced', null],
            ['$value_6', false, true, 6, null],
            ['$value_2', false, true, 2, '\\'.Php74TestClass::class.'::$value_2'],
            ['$value_uninit', false, true, null, null],
            [Php74TestClass::class.'::$value_1', false, true, 1, '\\'.Php74TestClass::class.'::$value_1'],
            [Php74TestClass::class.'::$value_3', false, true, 3, null],
            [Php74TestClass::class.'::$value_4', false, true, 4, null],
        ];

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(\count($expected), $rep->contents);

        foreach ($expected as $index => $expect) {
            $value = $rep->contents[$index];

            $this->assertSame($expect[0], $value->name);
            $this->assertSame($expect[1], $value->const);
            $this->assertSame($expect[2], $value->static);
            $this->assertSame($expect[4], $value->access_path);

            if ('$value_uninit' === $expect[0]) {
                $this->assertSame('uninitialized', $value->type);
                $this->assertNull($value->value);
            } else {
                $this->assertSame($expect[3], $value->value->contents);
            }
        }

        $expected_copy = $expected;
        $expected_copy[2][4] = '\\'.Php74ChildTestClass::class.'::$value_3';
        $expected_copy[3][4] = '\\'.Php74ChildTestClass::class.'::$value_6';
        $expected_copy[5][4] = '\\'.Php74TestClass::class.'::$value_uninit';

        $p->setCallerClass(Php74ChildTestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(\count($expected), $rep->contents);

        foreach ($expected_copy as $index => $expect) {
            $this->assertSame($expect[4], $rep->contents[$index]->access_path);
        }

        $expected_copy = $expected;
        $expected_copy[5][4] = '\\'.Php74TestClass::class.'::$value_uninit';
        $expected_copy[7][4] = '\\'.Php74TestClass::class.'::$value_3';
        $expected_copy[8][4] = '\\'.Php74TestClass::class.'::$value_4';

        $p->setCallerClass(Php74TestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('statics');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(\count($expected), $rep->contents);

        foreach ($expected_copy as $index => $expect) {
            $this->assertSame($expect[4], $rep->contents[$index]->access_path);
        }
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parse
     * @covers \Kint\Parser\ClassStaticsPlugin::getCachedConstants
     */
    public function testParseConstants()
    {
        $p = new Parser(5);

        $b = new Value('$v');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('constants'));

        $p->addPlugin(new ClassStaticsPlugin($p));

        $expected = [
            ['VALUE_1', true, false, 'replaced', '\\'.Php74ChildTestClass::class.'::VALUE_1'],
            ['VALUE_5', true, false, 5, '\\'.Php74ChildTestClass::class.'::VALUE_5'],
            ['VALUE_3', true, false, 'replaced', null],
            ['VALUE_6', true, false, 6, null],
            ['VALUE_2', true, false, 2, '\\'.Php74TestClass::class.'::VALUE_2'],
            ['VALUE_ARRAY', true, false, [], null],
            [Php74TestClass::class.'::VALUE_1', true, false, 1, '\\'.Php74TestClass::class.'::VALUE_1'],
            [Php74TestClass::class.'::VALUE_3', true, false, 3, null],
            [Php74TestClass::class.'::VALUE_4', true, false, 4, null],
        ];

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('constants');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(\count($expected), $rep->contents);

        $array_key = null;

        foreach ($expected as $index => $expect) {
            $value = $rep->contents[$index];

            $this->assertSame($expect[0], $value->name);
            $this->assertSame($expect[1], $value->const);
            $this->assertSame($expect[2], $value->static);
            if ([] === $expect[3]) {
                $this->assertArrayHasKey('depth_limit', $value->hints);
                $array_key = $index;
            } else {
                $this->assertSame($expect[3], $value->value->contents);
            }
            $this->assertSame($expect[4], $value->access_path);
        }

        $p->setDepthLimit(0);
        $o = $p->parse($v, clone $b);
        $this->assertArrayNotHasKey('depth_limit', $o->getRepresentation('constants')->contents[$array_key]->hints);
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parse
     * @covers \Kint\Parser\ClassStaticsPlugin::getCachedConstants
     */
    public function testParseEnumConstant()
    {
        if (!KINT_PHP81) {
            $this->markTestSkipped('Not testing ClassStaticsPlugin on enums below PHP 8.1');
        }

        $p = new Parser(5);
        $p->addPlugin(new ClassStaticsPlugin($p));

        $b = new Value('$v');
        $b->access_path = '$v';
        $v = Php81TestEnum::Hearts;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('statics'));

        $v = Php81TestBackedEnum::Hearts;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('statics'));
    }

    /**
     * @covers \Kint\Parser\ClassStaticsPlugin::parse
     */
    public function testParseBadValue()
    {
        $p = new Parser(5);
        $csp = new ClassStaticsPlugin($p);

        $v = null;
        $b = new Value('$v');
        $csp->parse($v, $b, Parser::TRIGGER_SUCCESS);

        $this->assertNull($b->getRepresentation('methods'));
    }
}
