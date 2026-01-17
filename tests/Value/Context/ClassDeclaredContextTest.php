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

namespace Kint\Test\Value\Context;

use __PHP_Incomplete_Class;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\Fixtures\OtherChildTestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\PropertyContext;

/**
 * @coversNothing
 */
class ClassDeclaredContextTest extends KintTestCase
{
    public function isAccessibleProvider()
    {
        $pub = new PropertyContext('name', TestClass::class, ClassDeclaredContext::ACCESS_PUBLIC);
        $pro = new PropertyContext('name', TestClass::class, ClassDeclaredContext::ACCESS_PROTECTED);
        $pro->proto_class = TestClass::class;
        $pri = new PropertyContext('name', TestClass::class, ClassDeclaredContext::ACCESS_PRIVATE);

        $child_pub = new PropertyContext('name', ChildTestClass::class, ClassDeclaredContext::ACCESS_PUBLIC);
        $child_pro = new PropertyContext('name', ChildTestClass::class, ClassDeclaredContext::ACCESS_PROTECTED);
        $child_pro->proto_class = TestClass::class;
        $child_pri = new PropertyContext('name', ChildTestClass::class, ClassDeclaredContext::ACCESS_PRIVATE);

        return [
            'global public' => [
                $pub,
                null,
                true,
            ],
            'global protected' => [
                $pro,
                null,
                false,
            ],
            'global private' => [
                $pri,
                null,
                false,
            ],
            'child public' => [
                $pub,
                ChildTestClass::class,
                true,
            ],
            'child protected' => [
                $pro,
                ChildTestClass::class,
                true,
            ],
            'child private' => [
                $pri,
                ChildTestClass::class,
                false,
            ],
            'parent public' => [
                $child_pub,
                TestClass::class,
                true,
            ],
            'parent protected' => [
                $child_pro,
                TestClass::class,
                true,
            ],
            'parent private' => [
                $child_pri,
                TestClass::class,
                false,
            ],
            // There is no need to test reverse siblings (ie. accessing an inherited property from
            // a child of the parent that redeclared it) because then the declaring class of the
            // property is the parent and we are identical to  the 'child protected' test case
            'sibling public' => [
                $child_pub,
                OtherChildTestClass::class,
                true,
            ],
            'sibling protected' => [
                $child_pro,
                OtherChildTestClass::class,
                KINT_PHP8412,
            ],
            'sibling private' => [
                $child_pri,
                OtherChildTestClass::class,
                false,
            ],
            'local public' => [
                $pub,
                TestClass::class,
                true,
            ],
            'local protected' => [
                $pro,
                TestClass::class,
                true,
            ],
            'local private' => [
                $pri,
                TestClass::class,
                true,
            ],
            'foreign public' => [
                $pub,
                self::class,
                true,
            ],
            'foreign protected' => [
                $pro,
                self::class,
                false,
            ],
            'foreign private' => [
                $pri,
                self::class,
                false,
            ],
            'incomplete property' => [
                new PropertyContext('name', __PHP_Incomplete_Class::class, ClassDeclaredContext::ACCESS_PUBLIC),
                __PHP_Incomplete_Class::class,
                false,
            ],
        ];
    }

    /**
     * @dataProvider isAccessibleProvider
     *
     * @covers \Kint\Value\Context\ClassDeclaredContext::isAccessible
     */
    public function testIsAccessible(ClassDeclaredContext $prop, ?string $scope, bool $expected)
    {
        $this->assertSame($expected, $prop->isAccessible($scope));
    }

    /**
     * @covers \Kint\Value\Context\ClassDeclaredContext::__construct
     */
    public function testConstruct()
    {
        $c = new PropertyContext('name', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $c->access);
    }

    /**
     * @covers \Kint\Value\Context\ClassDeclaredContext::getAccess
     */
    public function testGetAccess()
    {
        $c = new class('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC) extends ClassDeclaredContext {
            public function getModifiers(): string
            {
                return $this->getAccess();
            }
        };
        $this->assertSame('public', $c->getModifiers());
        $c->access = ClassDeclaredContext::ACCESS_PROTECTED;
        $this->assertSame('protected', $c->getModifiers());
        $c->access = ClassDeclaredContext::ACCESS_PRIVATE;
        $this->assertSame('private', $c->getModifiers());
    }
}
