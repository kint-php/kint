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

use Kint\Parser\AbstractPlugin;
use Kint\Parser\ClassMethodsPlugin;
use Kint\Parser\ClassStaticsPlugin;
use Kint\Parser\ClassStringsPlugin;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\Php74ChildTestClass;
use Kint\Test\KintTestCase;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\ContainerRepresentation;
use ReflectionClass;

/**
 * @coversNothing
 */
class ClassStringsPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\ClassStringsPlugin::__construct
     * @covers \Kint\Parser\ClassStringsPlugin::setParser
     */
    public function testConstruct()
    {
        $p = new Parser();
        $d = new ClassStringsPlugin($p);

        $reflector = new ReflectionClass($d);
        $mprop = $reflector->getProperty('methods_plugin');
        $mprop->setAccessible(true);
        $m = $mprop->getValue($d);
        $this->assertInstanceOf(ClassMethodsPlugin::class, $m);
        $sprop = $reflector->getProperty('statics_plugin');
        $sprop->setAccessible(true);
        $s = $sprop->getValue($d);
        $this->assertInstanceOf(ClassStaticsPlugin::class, $s);

        $reflector = new ReflectionClass(AbstractPlugin::class);
        $aparser = $reflector->getProperty('parser');
        $aparser->setAccessible(true);

        $p = new Parser();
        $this->assertNotSame($p, $aparser->getValue($m));
        $this->assertNotSame($p, $aparser->getValue($s));
        $d->setParser($p);
        $this->assertSame($p, $aparser->getValue($m));
        $this->assertSame($p, $aparser->getValue($s));
    }

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
     * @covers \Kint\Parser\ClassStringsPlugin::parseComplete
     */
    public function testParse()
    {
        $p = new Parser(5);

        $b = new BaseContext('Php74ChildTestClass::class');
        $b->access_path = 'Php74ChildTestClass::class';
        $v = Php74ChildTestClass::class;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('methods'));
        $this->assertNull($o->getRepresentation('static_methods'));
        $this->assertNull($o->getRepresentation('statics'));
        $this->assertNull($o->getRepresentation('constants'));

        $p->addPlugin(new ClassStringsPlugin($p));

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('methods'));
        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('static_methods'));
        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('statics'));
        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('constants'));
    }

    /**
     * @covers \Kint\Parser\ClassStringsPlugin::parseComplete
     */
    public function testParseNonexistant()
    {
        $p = new Parser(5);

        $b = new BaseContext('Php74ChildTestClass::class');
        $b->access_path = 'Php74ChildTestClass::class';
        $v = Php74ChildTestClass::class;

        $p->addPlugin(new ClassStringsPlugin($p));

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('methods'));
        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('statics'));

        $v = ThisClassDoesNotExist::class;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('methods'));
        $this->assertNull($o->getRepresentation('statics'));
    }

    /**
     * @covers \Kint\Parser\ClassStringsPlugin::parseComplete
     */
    public function testParseDeep()
    {
        $p = new Parser(5);

        $b = new BaseContext('Php74ChildTestClass::class');
        $b->access_path = 'Php74ChildTestClass::class';
        $v = Php74ChildTestClass::class;

        $p->addPlugin(new ClassStringsPlugin($p));

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('methods'));
        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('statics'));

        $b->depth = 1;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('methods'));
        $this->assertNull($o->getRepresentation('statics'));
    }

    /**
     * @covers \Kint\Parser\ClassStringsPlugin::parseComplete
     */
    public function testParseBlacklist()
    {
        $p = new Parser(5);

        $b = new BaseContext('Php74ChildTestClass::class');
        $b->access_path = 'Php74ChildTestClass::class';
        $v = Php74ChildTestClass::class;

        $p->addPlugin(new ClassStringsPlugin($p));

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('methods'));
        $this->assertInstanceOf(ContainerRepresentation::class, $o->getRepresentation('statics'));

        ClassStringsPlugin::$blacklist[] = Php74ChildTestClass::class;

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('methods'));
        $this->assertNull($o->getRepresentation('statics'));
    }
}
