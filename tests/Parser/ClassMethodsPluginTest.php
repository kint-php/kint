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

use Kint\Parser\ClassMethodsPlugin;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\Php74ChildTestClass;
use Kint\Test\Fixtures\Php74TestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\MethodContext;
use Kint\Value\FixedWidthValue;
use Kint\Value\MethodValue;
use Kint\Value\Representation\Representation;

/**
 * @coversNothing
 */
class ClassMethodsPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\ClassMethodsPlugin::getTriggers
     * @covers \Kint\Parser\ClassMethodsPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new ClassMethodsPlugin($this->createStub(Parser::class));

        $this->assertSame(['object'], $p->getTypes());
        $this->assertSame(Parser::TRIGGER_SUCCESS, $p->getTriggers());
    }

    /**
     * @covers \Kint\Parser\ClassMethodsPlugin::parseComplete
     */
    public function testParse()
    {
        $p = new Parser(5);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('methods'));

        $p->addPlugin(new ClassMethodsPlugin($p));

        $expected = [
            ['test1()', 'test1', false, '$v->test1()'],
            ['test2()', 'test2', true, '\\'.Php74ChildTestClass::class.'::test2()'],
            ['test9()', 'test9', false, '$v->test9()'],
            ['test10()', 'test10', true, '\\'.Php74ChildTestClass::class.'::test10()'],
            ['test5()', 'test5', false, null],
            ['test6()', 'test6', true, null],
            ['test11()', 'test11', false, null],
            ['test12()', 'test12', true, null],

            ['test3()', 'test3', false, '$v->test3()'],
            ['test4()', 'test4', true, '\\'.Php74TestClass::class.'::test4()'],

            [Php74TestClass::class.'::test2()', Php74TestClass::class.'::test2', true, '\\'.Php74TestClass::class.'::test2()'],
            [Php74TestClass::class.'::test5()', Php74TestClass::class.'::test5', false, null],
            [Php74TestClass::class.'::test6()', Php74TestClass::class.'::test6', true, null],
            [Php74TestClass::class.'::test7()', Php74TestClass::class.'::test7', false, null],
            [Php74TestClass::class.'::test8()', Php74TestClass::class.'::test8', true, null],
        ];

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('methods');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(15, $rep->contents);

        $array_key = null;

        foreach ($expected as $index => $expect) {
            $value = $rep->contents[$index];

            $this->assertInstanceOf(MethodValue::class, $value);
            $this->assertSame($expect[0], $value->getDisplayName());
            $this->assertInstanceOf(MethodContext::class, $value->getContext());
            $this->assertSame($expect[1], $value->getContext()->getName());
            $this->assertSame($expect[2], $value->getContext()->static);
            $this->assertSame($expect[3], $value->getContext()->getAccessPath());
        }

        $expected_copy = $expected;
        $expected_copy[4][3] = '$v->test5()';
        $expected_copy[5][3] = '\\'.Php74ChildTestClass::class.'::test6()';
        $expected_copy[6][3] = '$v->test11()';
        $expected_copy[7][3] = '\\'.Php74ChildTestClass::class.'::test12()';

        $p->setCallerClass(Php74ChildTestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('methods');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(15, $rep->contents);

        foreach ($expected_copy as $index => $expect) {
            $this->assertSame($expect[3], $rep->contents[$index]->getContext()->getAccessPath());
        }

        $expected_copy = $expected;
        $expected_copy[11][3] = '$v->test5()';
        $expected_copy[12][3] = '\\'.Php74TestClass::class.'::test6()';
        $expected_copy[13][3] = '$v->test7()';
        $expected_copy[14][3] = '\\'.Php74TestClass::class.'::test8()';

        $p->setCallerClass(Php74TestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('methods');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(15, $rep->contents);

        foreach ($expected_copy as $index => $expect) {
            $this->assertSame($expect[3], $rep->contents[$index]->getContext()->getAccessPath());
        }
    }

    /**
     * @covers \Kint\Parser\ClassMethodsPlugin::parseComplete
     */
    public function testParseWithParams()
    {
        $p = new Parser(5);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new TestClass();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('methods'));

        $p->addPlugin(new ClassMethodsPlugin($p));

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('methods');

        $mix = $rep->contents[7];

        $this->assertSame('mix(array &$x, ?Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\')', $mix->getDisplayName());
        $this->assertSame('mix', $mix->getContext()->getName());
        $this->assertSame('final protected static', $mix->getContext()->getModifiers());
        $this->assertTrue($mix->getContext()->static);
        $this->assertTrue($mix->getContext()->final);
        $this->assertTrue($mix->getCallableBag()->return_reference);
    }

    /**
     * @covers \Kint\Parser\ClassMethodsPlugin::parseComplete
     */
    public function testParseInvalidValue()
    {
        $p = new Parser(5);
        $cmp = new ClassMethodsPlugin($p);

        $v = null;
        $b = new FixedWidthValue(new BaseContext('$v'), $v);
        $cmp->parseComplete($v, $b, Parser::TRIGGER_SUCCESS);

        $this->assertNull($b->getRepresentation('methods'));
    }
}
