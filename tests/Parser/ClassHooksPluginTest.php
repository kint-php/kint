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
use Kint\Zval\Value;

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
     * @covers \Kint\Parser\ClassHooksPlugin::parse
     */
    public function testParse()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing ClassHooksPlugin below PHP 8.4');
        }

        $p = new Parser(5);

        $b = new Value('$v');
        $b->access_path = '$v';
        $v = new Php84ChildTestClass();

        $o = $p->parse($v, clone $b);

        foreach ($o->value->contents as $prop) {
            $this->assertNull($prop->getRepresentation('propertyhooks'));
        }

        $chp = new ClassHooksPlugin($p);
        $p->addPlugin($chp);
        $o = $p->parse($v, clone $b);

        $props = [];
        foreach ($o->value->contents as $prop) {
            $props[$prop->name] = $prop->getRepresentation('propertyhooks')->contents ?? null;
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
        foreach ($o->value->contents as $prop) {
            $props[$prop->name] = $prop->getRepresentation('propertyhooks')->contents ?? null;
        }

        $this->assertNotNull($props['a']);
        $this->assertNotNull($props['b']);
        $this->assertNotNull($props['c']);
        $this->assertNotNull($props['d']);
        $this->assertNotNull($props['e']);
        $this->assertNotNull($props['f']);

        $this->assertSame('$a::get', $props['a'][0]->name);
        $this->assertSame(Php84ChildTestClass::class, $props['a'][0]->owner_class);
        $this->assertFalse($props['a'][0]->return_reference);
        $this->assertSame('', $props['a'][0]->getParams());
        $this->assertSame('$a::set', $props['a'][1]->name);
        $this->assertSame(Php84ChildTestClass::class, $props['a'][1]->owner_class);
        $this->assertFalse($props['a'][1]->return_reference);
        $this->assertSame('int $value', $props['a'][1]->getParams());

        $this->assertSame('$b::get', $props['b'][0]->name);
        $this->assertSame(Php84TestClass::class, $props['b'][0]->owner_class);
        $this->assertFalse($props['b'][0]->return_reference);
        $this->assertSame('', $props['b'][0]->getParams());

        $this->assertSame('$c::set', $props['c'][0]->name);
        $this->assertSame(Php84TestClass::class, $props['c'][0]->owner_class);
        $this->assertFalse($props['c'][0]->return_reference);
        $this->assertSame('int $value', $props['c'][0]->getParams());

        $this->assertSame('$d::get', $props['d'][0]->name);
        $this->assertSame(Php84TestClass::class, $props['d'][0]->owner_class);
        $this->assertFalse($props['d'][0]->return_reference);
        $this->assertSame('', $props['d'][0]->getParams());
        $this->assertSame('$d::set', $props['d'][1]->name);
        $this->assertSame(Php84TestClass::class, $props['d'][1]->owner_class);
        $this->assertFalse($props['d'][1]->return_reference);
        $this->assertSame('int|float $fancyPantsName', $props['d'][1]->getParams());

        $this->assertSame('$e::get', $props['e'][0]->name);
        $this->assertSame(Php84TestClass::class, $props['e'][0]->owner_class);
        $this->assertTrue($props['e'][0]->return_reference);
        $this->assertSame('', $props['e'][0]->getParams());

        $this->assertSame('$f::set', $props['f'][0]->name);
        $this->assertSame(Php84TestClass::class, $props['f'][0]->owner_class);
        $this->assertFalse($props['f'][0]->return_reference);
        $this->assertSame('$value', $props['f'][0]->getParams());
    }
}
