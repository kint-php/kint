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

use Kint\Test\Fixtures\Php7TestClass;
use Kint\Test\Fixtures\Php81TestClass;
use Kint\Test\Fixtures\Php8TestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\DeclaredCallableBag;
use ReflectionFunction;
use ReflectionMethod;

/**
 * @coversNothing
 */
class ParameterHoldingTraitTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\ParameterHoldingTrait::getParams
     */
    public function testGetParams()
    {
        $m = new DeclaredCallableBag(new ReflectionFunction('explode'));
        if (KINT_PHP80) {
            $this->assertSame('string $separator, string $string, int $limit = '.PHP_INT_MAX, $m->getParams());
        } else {
            $this->assertSame('$separator, $str, $limit', $m->getParams());
        }

        $m = new DeclaredCallableBag(new ReflectionMethod(TestClass::class, 'arrayHint'));
        $this->assertSame('array $x', $m->getParams());

        $m = new DeclaredCallableBag(new ReflectionMethod(TestClass::class, 'classHint'));
        $this->assertSame('Kint\\Test\\Fixtures\\TestClass $x', $m->getParams());

        $m = new DeclaredCallableBag(new ReflectionMethod(TestClass::class, 'ref'));
        $this->assertSame('&$x', $m->getParams());

        $m = new DeclaredCallableBag(new ReflectionMethod(TestClass::class, 'defaultMethod'));
        $this->assertSame('$x = 1234', $m->getParams());

        $m = new DeclaredCallableBag(new ReflectionMethod(TestClass::class, 'mix'));

        $this->assertSame(
            'array &$x, ?Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\'',
            $m->getParams()
        );

        $m = new DeclaredCallableBag(new ReflectionMethod(Php7TestClass::class, 'typeHints'));
        $this->assertSame('string $p1, int $p2, bool $p3 = false, int $p4 = 0, int $p5 = 1', $m->getParams());

        if (KINT_PHP80) {
            $m = new DeclaredCallableBag(new ReflectionMethod(Php8TestClass::class, 'typeHints'));
            $this->assertSame('string|int $p1, ?int $p2, bool $p3 = false, ?string $nullable = null, string|int|null $nullable2 = null, mixed $mixed = null, $untyped = null', $m->getParams());
        }

        if (KINT_PHP81) {
            $m = new DeclaredCallableBag(new ReflectionMethod(Php81TestClass::class, 'typeHints'));
            $this->assertSame('X&Y $p1, $p2 = object(Kint\\Test\\Fixtures\\Php81TestClass)', $m->getParams());
        }
    }
}
