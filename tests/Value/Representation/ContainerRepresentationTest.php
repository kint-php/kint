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

namespace Kint\Test\Value\Representation;

use InvalidArgumentException;
use Kint\Test\KintTestCase;
use Kint\Value\Context\BaseContext;
use Kint\Value\FixedWidthValue;
use Kint\Value\Representation\ContainerRepresentation;

/**
 * @coversNothing
 */
class ContainerRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\Representation\ContainerRepresentation::__construct
     * @covers \Kint\Value\Representation\ContainerRepresentation::getContents
     */
    public function testConstruct()
    {
        $v = new FixedWidthValue(new BaseContext('$v'), 1);
        $r = new ContainerRepresentation('My Label', [$v]);

        $this->assertSame([$v], $r->getContents());

        $r = new ContainerRepresentation('My Label', [$v, $v]);
        $this->assertSame([$v, $v], $r->getContents());
    }

    /**
     * @covers \Kint\Value\Representation\ContainerRepresentation::__construct
     */
    public function testNoEmptyList()
    {
        $this->expectException(InvalidArgumentException::class);

        $r = new ContainerRepresentation('My Label', []);
    }

    /**
     * @covers \Kint\Value\Representation\ContainerRepresentation::getLabel
     */
    public function testGetLabel()
    {
        $v = new FixedWidthValue(new BaseContext('$v'), 1);
        $r = new ContainerRepresentation('My Label', [$v]);
        $this->assertSame('My Label (1)', $r->getLabel());

        $v = new FixedWidthValue(new BaseContext('$v'), 1);
        $r = new ContainerRepresentation('My Label', [$v, $v]);
        $this->assertSame('My Label (2)', $r->getLabel());
    }
}
