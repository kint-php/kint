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

namespace Kint\Test\Value;

use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\Fixtures\Php71TestClass;
use Kint\Test\Fixtures\Php7TestClass;
use Kint\Test\Fixtures\Php8TestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\DeclaredCallableBag;
use ReflectionMethod;

/**
 * @coversNothing
 */
class DeclaredCallableBagTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\DeclaredCallableBag::__construct
     */
    public function testConstruct()
    {
        $reflection = new ReflectionMethod(TestClass::class, 'mix');
        $b = new DeclaredCallableBag($reflection);

        $this->assertSame($reflection->getFileName(), $b->filename);
        $this->assertSame($reflection->getStartLine(), $b->startline);
        $this->assertSame($reflection->getEndLine(), $b->endline);
        $this->assertFalse($b->internal);
        $this->assertTrue($b->return_reference);
        $this->assertNull($b->docstring);
    }

    /**
     * @covers \Kint\Value\DeclaredCallableBag::__construct
     */
    public function testConstructParentDocstring()
    {
        $reflection = new ReflectionMethod(TestClass::class, '__construct');
        $b = new DeclaredCallableBag($reflection);
        $this->assertSame($reflection->getDocComment(), $b->docstring);

        $child_reflection = new ReflectionMethod(ChildTestClass::class, '__construct');
        $b = new DeclaredCallableBag($child_reflection);
        $this->assertSame($reflection->getDocComment(), $b->docstring);
    }

    /**
     * @covers \Kint\Value\DeclaredCallableBag::__construct
     */
    public function testReturnType()
    {
        $b = new DeclaredCallableBag(new ReflectionMethod(Php7TestClass::class, 'typeHints'));
        $this->assertSame('self', $b->returntype);

        $b = new DeclaredCallableBag(new ReflectionMethod(Php71TestClass::class, 'typeHints'));
        $this->assertSame('?self', $b->returntype);

        $b = new DeclaredCallableBag(new ReflectionMethod(Php71TestClass::class, 'returnTypeHint'));
        $this->assertSame('?Kint\\Test\\Fixtures\\TestClass', $b->returntype);

        if (KINT_PHP80) {
            $b = new DeclaredCallableBag(new ReflectionMethod(Php8TestClass::class, 'typeHints'));
            $this->assertSame('?static', $b->returntype);
        }
    }
}
