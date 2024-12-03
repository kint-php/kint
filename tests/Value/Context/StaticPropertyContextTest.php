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

use Kint\Test\KintTestCase;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\StaticPropertyContext;

/**
 * @coversNothing
 */
class StaticPropertyContextTest extends KintTestCase
{
    public function modifierProvider()
    {
        $pub_static_final = new StaticPropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $pub_static_final->final = true;
        $pro_static_final = new StaticPropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PROTECTED);
        $pro_static_final->final = true;

        return [
            'public static' => [
                new StaticPropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC),
                'public static',
            ],
            'protected static' => [
                new StaticPropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PROTECTED),
                'protected static',
            ],
            'private static' => [
                new StaticPropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PRIVATE),
                'private static',
            ],
            'final public static' => [
                $pub_static_final,
                'final public static',
            ],
            'final protected static' => [
                $pro_static_final,
                'final protected static',
            ],
        ];
    }

    /**
     * @dataProvider modifierProvider
     *
     * @covers \Kint\Value\Context\StaticPropertyContext::getModifiers
     */
    public function testGetModifiers(StaticPropertyContext $c, string $expect)
    {
        $this->assertSame($expect, $c->getModifiers());
    }

    /**
     * @covers \Kint\Value\Context\StaticPropertyContext::getName
     */
    public function testGetName()
    {
        $c = new StaticPropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $this->assertSame('class::$base', $c->getName());
    }

    /**
     * @covers \Kint\Value\Context\StaticPropertyContext::getOperator
     */
    public function testGetOperator()
    {
        $c = new StaticPropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $this->assertSame('::', $c->getOperator());
    }
}
