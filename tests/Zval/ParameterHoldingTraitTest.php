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

namespace Kint\Test\Zval;

use Kint\Zval\MethodValue;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;

/**
 * @coversNothing
 */
class ParameterHoldingTraitTest extends TestCase
{
    /**
     * @covers \Kint\Zval\ParameterHoldingTrait::getParams
     */
    public function testGetParams()
    {
        $m = new MethodValue(new ReflectionFunction('explode'));
        if (KINT_PHP80) {
            $this->assertSame('string $separator, string $string, int $limit = '.PHP_INT_MAX, $m->getParams());
        } else {
            $this->assertSame('$separator, $str, $limit', $m->getParams());
        }

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'arrayHint'));
        $this->assertSame('array $x', $m->getParams());

        // Testing cache
        $m->parameters = [];
        $this->assertSame('array $x', $m->getParams());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'classHint'));
        $this->assertSame('Kint\\Test\\Fixtures\\TestClass $x', $m->getParams());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'ref'));
        $this->assertSame('&$x', $m->getParams());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'defaultMethod'));
        $this->assertSame('$x = 1234', $m->getParams());

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\TestClass', 'mix'));

        $this->assertSame(
            'array &$x, ?Kint\\Test\\Fixtures\\TestClass $y = null, $z = array(...), $_ = \'string\'',
            $m->getParams()
        );

        $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\Php7TestClass', 'typeHints'));
        $this->assertSame('string $p1, int $p2, bool $p3 = false', $m->getParams());

        if (KINT_PHP80) {
            $m = new MethodValue(new ReflectionMethod('Kint\\Test\\Fixtures\\Php8TestClass', 'typeHints'));
            $this->assertSame('string|int $p1, ?int $p2, bool $p3 = false, ?string $nullable = null, string|int|null $nullable2 = null, mixed $mixed = null, $untyped = null', $m->getParams());
        }
    }
}
