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

use Kint\Parser\ClassStringsPlugin;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\Php74ChildTestClass;
use Kint\Test\KintTestCase;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

/**
 * @coversNothing
 */
class ClassStringsPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\ClassStringsPlugin::getTriggers
     * @covers \Kint\Parser\ClassStringsPlugin::getTypes
     */
    public function testHooks()
    {
        $p = new ClassStringsPlugin($this->createStub(Parser::class));

        $this->assertSame(['string'], $p->getTypes());
        $this->assertSame(Parser::TRIGGER_SUCCESS, $p->getTriggers());
    }

    /**
     * @covers \Kint\Parser\ClassStringsPlugin::parse
     */
    public function testParse()
    {
        $p = new Parser(5);

        $b = new Value('Php74ChildTestClass::class');
        $b->access_path = 'Php74ChildTestClass::class';
        $v = Php74ChildTestClass::class;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('methods'));
        $this->assertNull($o->getRepresentation('statics'));

        $p->addPlugin(new ClassStringsPlugin($p));

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(Representation::class, $o->getRepresentation('methods'));
        $this->assertInstanceOf(Representation::class, $o->getRepresentation('statics'));
    }
}
