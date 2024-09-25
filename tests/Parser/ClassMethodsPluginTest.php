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
use Kint\Test\KintTestCase;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

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
     * @covers \Kint\Parser\ClassMethodsPlugin::parse
     */
    public function testParse()
    {
        $p = new Parser(5);

        $b = new Value('$v');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('methods'));

        $p->addPlugin(new ClassMethodsPlugin($p));

        $expected = [
            ['test1', false, '$v->test1'],
            ['test2', true, '\\'.Php74ChildTestClass::class.'::test2'],
            ['test9', false, '$v->test9'],
            ['test10', true, '\\'.Php74ChildTestClass::class.'::test10'],
            ['test5', false, null],
            ['test6', true, null],
            ['test11', false, null],
            ['test12', true, null],

            ['test3', false, '$v->test3'],
            ['test4', true, '\\'.Php74TestClass::class.'::test4'],

            [Php74TestClass::class.'::test2', true, '\\'.Php74TestClass::class.'::test2'],
            [Php74TestClass::class.'::test5', false, null],
            [Php74TestClass::class.'::test6', true, null],
            [Php74TestClass::class.'::test7', false, null],
            [Php74TestClass::class.'::test8', true, null],
        ];

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('methods');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(15, $rep->contents);

        $array_key = null;

        foreach ($expected as $index => $expect) {
            $value = $rep->contents[$index];

            $this->assertSame($expect[0], $value->name);
            $this->assertSame($expect[1], $value->static);
            $this->assertSame($expect[2], $value->access_path);
        }

        $expected_copy = $expected;
        $expected_copy[4][2] = '$v->test5';
        $expected_copy[5][2] = '\\'.Php74ChildTestClass::class.'::test6';
        $expected_copy[6][2] = '$v->test11';
        $expected_copy[7][2] = '\\'.Php74ChildTestClass::class.'::test12';

        $p->setCallerClass(Php74ChildTestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('methods');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(15, $rep->contents);

        foreach ($expected_copy as $index => $expect) {
            $this->assertSame($expect[2], $rep->contents[$index]->access_path);
        }

        $expected_copy = $expected;
        $expected_copy[11][2] = '$v->test5';
        $expected_copy[12][2] = '\\'.Php74TestClass::class.'::test6';
        $expected_copy[13][2] = '$v->test7';
        $expected_copy[14][2] = '\\'.Php74TestClass::class.'::test8';

        $p->setCallerClass(Php74TestClass::class);
        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('methods');
        $this->assertInstanceOf(Representation::class, $rep);
        $this->assertCount(15, $rep->contents);

        foreach ($expected_copy as $index => $expect) {
            $this->assertSame($expect[2], $rep->contents[$index]->access_path);
        }
    }

    /**
     * @covers \Kint\Parser\ClassMethodsPlugin::parse
     */
    public function testParseInvalidValue()
    {
        $p = new Parser(5);
        $cmp = new ClassMethodsPlugin($p);

        $v = null;
        $b = new Value('$v');
        $cmp->parse($v, $b, Parser::TRIGGER_SUCCESS);

        $this->assertNull($b->getRepresentation('methods'));
    }
}
