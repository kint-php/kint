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
use Kint\Parser\Parser;
use Kint\Parser\PluginInterface;
use Kint\Test\Fixtures\MethodChildTestClass;
use Kint\Test\Fixtures\MethodParentTestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\MethodContext;
use Kint\Value\FixedWidthValue;
use Kint\Value\InstanceValue;
use Kint\Value\MethodValue;
use Kint\Value\Representation\ContainerRepresentation;
use ReflectionClass;

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

    public function expectedMethodsProvider()
    {
        $base_methods = [
            'test1' => '$v->test1()',
            'test9' => '$v->test9()',
            'test5' => null,
            'test11' => null,
            'test3' => '$v->test3()',
            MethodParentTestClass::class.'::__CONSTRUCT' => null,
            MethodParentTestClass::class.'::test5' => null,
            MethodParentTestClass::class.'::test7' => null,
        ];
        $base_static_methods = [
            MethodChildTestClass::class.'::test2' => null,
            MethodChildTestClass::class.'::test10' => null,
            MethodChildTestClass::class.'::test6' => null,
            MethodChildTestClass::class.'::test12' => null,
            MethodParentTestClass::class.'::make' => null,
            MethodParentTestClass::class.'::test4' => null,
            MethodParentTestClass::class.'::test2' => null,
            MethodParentTestClass::class.'::test6' => null,
            MethodParentTestClass::class.'::test8' => null,
        ];

        // Different order in 7.4
        if (!KINT_PHP80) {
            $base_methods = [
                'test1' => '$v->test1()',
                'test9' => '$v->test9()',
                'test5' => null,
                'test11' => null,
                MethodParentTestClass::class.'::__CONSTRUCT' => null,
                'test3' => '$v->test3()',
                MethodParentTestClass::class.'::test7' => null,
                MethodParentTestClass::class.'::test5' => null,
            ];
            $base_static_methods = [
                MethodChildTestClass::class.'::test2' => null,
                MethodChildTestClass::class.'::test10' => null,
                MethodChildTestClass::class.'::test6' => null,
                MethodChildTestClass::class.'::test12' => null,
                MethodParentTestClass::class.'::make' => null,
                MethodParentTestClass::class.'::test4' => null,
                MethodParentTestClass::class.'::test8' => null,
                MethodParentTestClass::class.'::test2' => null,
                MethodParentTestClass::class.'::test6' => null,
            ];
        }

        $child_methods = $base_methods;
        $child_methods['test5'] = '$v->test5()';
        $child_methods['test11'] = '$v->test11()';

        $parent_methods = $base_methods;
        $parent_methods[MethodParentTestClass::class.'::__CONSTRUCT'] = 'new \\'.MethodChildTestClass::class.'()';
        $parent_methods[MethodParentTestClass::class.'::test5'] = '$v->test5()';
        $parent_methods[MethodParentTestClass::class.'::test7'] = '$v->test7()';

        $empty_methods = $base_methods;
        $empty_methods['test1'] = null;
        $empty_methods['test9'] = null;
        $empty_methods['test3'] = null;

        $only_constructor_methods = $empty_methods;
        $only_constructor_methods[MethodParentTestClass::class.'::__CONSTRUCT'] = 'new \\'.MethodChildTestClass::class.'()';

        $reflection_methods = [];
        $reflection_static_methods = [];
        $r = new ReflectionClass(MethodChildTestClass::class);

        do {
            foreach ($r->getMethods() as $m) {
                if ($m->isStatic()) {
                    $reflection_static_methods[$m->class.'::'.$m->name] = null;
                } elseif ($m->isPrivate()) {
                    if (MethodChildTestClass::class !== $m->getDeclaringClass()->name) {
                        $reflection_methods[$m->class.'::'.$m->name] = null;
                    } else {
                        $reflection_methods[$m->name] = null;
                    }
                } else {
                    $reflection_methods[$m->name] = null;
                }
            }
        } while ($r = $r->getParentClass());

        return [
            'no scope' => [
                null,
                true,
                false,
                '$v',
                $base_methods,
                $base_static_methods,
            ],
            'child scope' => [
                MethodChildTestClass::class,
                true,
                false,
                '$v',
                $child_methods,
                $base_static_methods,
            ],
            'parent scope' => [
                MethodParentTestClass::class,
                true,
                false,
                '$v',
                $parent_methods,
                $base_static_methods,
            ],
            'no show ap' => [
                MethodParentTestClass::class,
                false,
                false,
                '$v',
                $empty_methods,
                $base_static_methods,
            ],
            'no object ap' => [
                MethodParentTestClass::class,
                true,
                false,
                null,
                $empty_methods,
                $base_static_methods,
            ],
            'no object ap with show constructor' => [
                MethodParentTestClass::class,
                true,
                true,
                null,
                $only_constructor_methods,
                $base_static_methods,
            ],
            'reflection order' => [
                null,
                false,
                false,
                null,
                $reflection_methods,
                $reflection_static_methods,
            ],
        ];
    }

    /**
     * @dataProvider expectedMethodsProvider
     *
     * @covers \Kint\Parser\ClassMethodsPlugin::parseComplete
     * @covers \Kint\Parser\ClassMethodsPlugin::getCachedMethods
     * @covers \Kint\Parser\ClassMethodsPlugin::getCachedStaticMethods
     */
    public function testParse($scope, $show_ap, $show_constructor, $object_ap, $expected, $expected_static)
    {
        $p = new Parser(5);
        $p->setCallerClass($scope);

        $b = new BaseContext('$v');
        $b->access_path = $object_ap;
        $v = MethodChildTestClass::make();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('methods'));

        $p->addPlugin(new ClassMethodsPlugin($p));

        ClassMethodsPlugin::$show_access_path = $show_ap;
        ClassMethodsPlugin::$show_constructor_path = $show_constructor;

        $o = $p->parse($v, clone $b);

        $rep = $o->getRepresentation('methods');
        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertCount(8, $rep->getContents());

        $contents = $rep->getContents();
        $value = \reset($contents);
        foreach ($expected as $name => $ap) {
            $this->assertInstanceOf(MethodValue::class, $value);
            $this->assertSame($name.'()', $value->getDisplayName());
            $this->assertInstanceOf(MethodContext::class, $value->getContext());
            $this->assertFalse($value->getContext()->static);
            $this->assertSame($ap, $value->getContext()->getAccessPath());
            $value = \next($contents);
        }
        $this->assertFalse($value);

        $rep = $o->getRepresentation('static_methods');
        $this->assertInstanceOf(ContainerRepresentation::class, $rep);
        $this->assertCount(9, $rep->getContents());

        $contents = $rep->getContents();
        $value = \reset($contents);
        foreach ($expected_static as $name => $ap) {
            $this->assertInstanceOf(MethodValue::class, $value);
            $this->assertSame($name.'()', $value->getDisplayName());
            $this->assertInstanceOf(MethodContext::class, $value->getContext());
            $this->assertTrue($value->getContext()->static);
            $this->assertSame($ap, $value->getContext()->getAccessPath());
            $value = \next($contents);
        }
        $this->assertFalse($value);
    }

    /**
     * @covers \Kint\Parser\ClassMethodsPlugin::parseComplete
     * @covers \Kint\Parser\ClassMethodsPlugin::getCachedMethods
     * @covers \Kint\Parser\ClassMethodsPlugin::getCachedStaticMethods
     */
    public function testParseWithParams()
    {
        $p = new Parser(5);

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new TestClass();

        $o = $p->parse($v, clone $b);

        $this->assertNull($o->getRepresentation('static_methods'));

        $p->addPlugin(new ClassMethodsPlugin($p));

        $o = $p->parse($v, clone $b);
        $rep = $o->getRepresentation('static_methods');

        $mix = \array_values($rep->getContents())[1];

        $this->assertSame(TestClass::class.'::mix(array &$x, ?Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\')', $mix->getDisplayName());
        $this->assertSame('mix', $mix->getContext()->getName());
        $this->assertSame('final protected static', $mix->getContext()->getModifiers());
        $this->assertTrue($mix->getContext()->static);
        $this->assertTrue($mix->getContext()->final);
        $this->assertTrue($mix->getCallableBag()->return_reference);
    }

    /**
     * @covers \Kint\Parser\ClassMethodsPlugin::parseComplete
     * @covers \Kint\Parser\ClassMethodsPlugin::getCachedMethods
     * @covers \Kint\Parser\ClassMethodsPlugin::getCachedStaticMethods
     */
    public function testParseWithAbstractFromInterfaceInHierarchy()
    {
        $p = new Parser(5);
        $p->addPlugin(new ClassMethodsPlugin($p));

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new ClassMethodsPlugin($p);

        $o = $p->parse($v, clone $b);

        $rep = $o->getRepresentation('methods');

        $m = \array_values($rep->getContents())[0];

        $this->assertInstanceOf(MethodValue::class, $m);
        $this->assertSame('getTypes', $m->getContext()->name);
        $this->assertFalse($m->getContext()->inherited);
        $this->assertFalse($m->getContext()->abstract);
        $this->assertSame(ClassMethodsPlugin::class, $m->getContext()->owner_class);

        $fakeC = new BaseContext('$v');
        $fakeC->access_path = null;
        $fakeV = new InstanceValue($fakeC, AbstractPlugin::class, 'badhash', -1);
        $fakeVar = null;

        $fakeO = $v->parseComplete($fakeVar, $fakeV, Parser::TRIGGER_SUCCESS);

        $rep = $fakeO->getRepresentation('methods');

        $m = \array_values($rep->getContents())[3];

        $this->assertInstanceOf(MethodValue::class, $m);
        $this->assertSame('getTypes', $m->getContext()->name);
        $this->assertTrue($m->getContext()->inherited);
        $this->assertTrue($m->getContext()->abstract);
        $this->assertSame(PluginInterface::class, $m->getContext()->owner_class);
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
