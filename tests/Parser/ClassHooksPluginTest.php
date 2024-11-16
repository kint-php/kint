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

use Kint\Parser\ClassHooksPlugin;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\Php84ChildTestClass;
use Kint\Test\Fixtures\Php84TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\Context\BaseContext;
use Kint\Value\FixedWidthValue;
use Kint\Value\InstanceValue;

/**
 * @coversNothing
 */
class ClassHooksPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\ClassHooksPlugin::getTriggers
     * @covers \Kint\Parser\ClassHooksPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new ClassHooksPlugin($this->createStub(Parser::class));

        $this->assertSame(['object'], $p->getTypes());
        $this->assertSame(KINT_PHP84 ? Parser::TRIGGER_SUCCESS : Parser::TRIGGER_NONE, $p->getTriggers());
    }

    /**
     * @covers \Kint\Parser\ClassHooksPlugin::parseComplete
     */
    public function testParse()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing ClassHooksPlugin below PHP 8.4');
        }

        $p = new Parser(5);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php84ChildTestClass();

        $o = $p->parse($v, clone $b);

        foreach ($o->getChildren() as $prop) {
            $this->assertNull($prop->getRepresentation('propertyhooks'));
        }

        $chp = new ClassHooksPlugin($p);
        $p->addPlugin($chp);
        $o = $p->parse($v, clone $b);

        $props = [];
        foreach ($o->getChildren() as $prop) {
            $props[$prop->getContext()->getName()] = $prop->getRepresentation('propertyhooks') ?? null;
        }

        $this->assertNull($props['a']);
        $this->assertNull($props['b']);
        $this->assertNotNull($props['c']);
        $this->assertNull($props['d']);
        $this->assertNull($props['e']);
        $this->assertNull($props['f']);

        ClassHooksPlugin::$verbose = true;
        $o = $p->parse($v, clone $b);

        $props = [];
        foreach ($o->getChildren() as $prop) {
            $hooks = $prop->getRepresentation('propertyhooks');
            if ($hooks) {
                $hooks = $hooks->getContents();
            }
            $props[$prop->getContext()->getName()] = $hooks ?? null;
        }

        $this->assertNotNull($props['a']);
        $this->assertNotNull($props['b']);
        $this->assertNotNull($props['c']);
        $this->assertNotNull($props['d']);
        $this->assertNotNull($props['e']);
        $this->assertNotNull($props['f']);

        $this->assertSame('$a::get()', $props['a'][0]->getDisplayName());
        $this->assertSame('$a::get', $props['a'][0]->getContext()->getName());
        $this->assertSame(Php84ChildTestClass::class, $props['a'][0]->getContext()->owner_class);
        $this->assertFalse($props['a'][0]->getCallableBag()->return_reference);
        $this->assertSame('', $props['a'][0]->getCallableBag()->getParams());
        $this->assertSame('$a::set(int $value)', $props['a'][1]->getDisplayName());
        $this->assertSame('$a::set', $props['a'][1]->getContext()->getName());
        $this->assertSame(Php84ChildTestClass::class, $props['a'][1]->getContext()->owner_class);
        $this->assertFalse($props['a'][1]->getCallableBag()->return_reference);
        $this->assertSame('int $value', $props['a'][1]->getCallableBag()->getParams());

        $this->assertSame('$b::get()', $props['b'][0]->getDisplayName());
        $this->assertSame('$b::get', $props['b'][0]->getContext()->getName());
        $this->assertSame(Php84TestClass::class, $props['b'][0]->getContext()->owner_class);
        $this->assertFalse($props['b'][0]->getCallableBag()->return_reference);
        $this->assertSame('', $props['b'][0]->getCallableBag()->getParams());

        $this->assertSame('$c::set(int $value)', $props['c'][0]->getDisplayName());
        $this->assertSame('$c::set', $props['c'][0]->getContext()->getName());
        $this->assertSame(Php84TestClass::class, $props['c'][0]->getContext()->owner_class);
        $this->assertFalse($props['c'][0]->getCallableBag()->return_reference);
        $this->assertSame('int $value', $props['c'][0]->getCallableBag()->getParams());

        $this->assertSame('$d::get()', $props['d'][0]->getDisplayName());
        $this->assertSame('$d::get', $props['d'][0]->getContext()->getName());
        $this->assertSame(Php84TestClass::class, $props['d'][0]->getContext()->owner_class);
        $this->assertFalse($props['d'][0]->getCallableBag()->return_reference);
        $this->assertSame('', $props['d'][0]->getCallableBag()->getParams());
        $this->assertSame('$d::set(int|float $fancyPantsName)', $props['d'][1]->getDisplayName());
        $this->assertSame('$d::set', $props['d'][1]->getContext()->getName());
        $this->assertSame(Php84TestClass::class, $props['d'][1]->getContext()->owner_class);
        $this->assertFalse($props['d'][1]->getCallableBag()->return_reference);
        $this->assertSame('int|float $fancyPantsName', $props['d'][1]->getCallableBag()->getParams());

        $this->assertSame('$e::get()', $props['e'][0]->getDisplayName());
        $this->assertSame('$e::get', $props['e'][0]->getContext()->getName());
        $this->assertSame(Php84TestClass::class, $props['e'][0]->getContext()->owner_class);
        $this->assertTrue($props['e'][0]->getCallableBag()->return_reference);
        $this->assertSame('', $props['e'][0]->getCallableBag()->getParams());

        $this->assertSame('$f::set($value)', $props['f'][0]->getDisplayName());
        $this->assertSame('$f::set', $props['f'][0]->getContext()->getName());
        $this->assertSame(Php84TestClass::class, $props['f'][0]->getContext()->owner_class);
        $this->assertFalse($props['f'][0]->getCallableBag()->return_reference);
        $this->assertSame('$value', $props['f'][0]->getCallableBag()->getParams());
    }

    /**
     * @covers \Kint\Parser\ClassHooksPlugin::parseComplete
     */
    public function testBadParse()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing ClassHooksPlugin below PHP 8.4');
        }

        $p = new Parser(5);
        $chp = new ClassHooksPlugin($p);
        $p->addPlugin($chp);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php84ChildTestClass();
        $o = new InstanceValue($b, \get_class($v), \spl_object_hash($v), \spl_object_id($v));

        $out = $chp->parseComplete($v, $o, Parser::TRIGGER_SUCCESS);
        $this->assertSame($o, $out);

        $o = new FixedWidthValue($b, 123);

        $out = $chp->parseComplete($v, $o, Parser::TRIGGER_SUCCESS);
        $this->assertSame($o, $out);
    }
}
