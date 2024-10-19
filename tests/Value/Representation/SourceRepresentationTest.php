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

use Kint\Test\KintTestCase;
use Kint\Value\Representation\SourceRepresentation;
use RuntimeException;

/**
 * @coversNothing
 */
class SourceRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\Representation\SourceRepresentation::__construct
     * @covers \Kint\Value\Representation\SourceRepresentation::getSourceLines
     * @covers \Kint\Value\Representation\SourceRepresentation::getSourceSlice
     * @covers \Kint\Value\Representation\SourceRepresentation::getHint
     * @covers \Kint\Value\Representation\SourceRepresentation::getFileName
     * @covers \Kint\Value\Representation\SourceRepresentation::getLine
     * @covers \Kint\Value\Representation\SourceRepresentation::readSource
     */
    public function testConstruct()
    {
        $source = \file_get_contents(__FILE__);
        $source = \explode("\n", $source);
        $source = [null, ...$source];

        $r = new SourceRepresentation(__FILE__, 1);
        $this->assertSame('source', $r->getHint());
        $this->assertSame('source', $r->getName());
        $this->assertSame(__FILE__, $r->getFileName());
        $this->assertSame(1, $r->getLine());
        $this->assertSame(\array_slice($source, 1, 8, true), $r->getSourceLines());

        $r = new SourceRepresentation(__FILE__, 45, 7);
        $this->assertSame(\array_slice($source, 45 - 7, 15, true), $r->getSourceLines());
        $this->assertSame(45, $r->getLine());

        $r = new SourceRepresentation(__FILE__, 1, 9);
        $this->assertSame(\array_slice($source, 1, 10, true), $r->getSourceLines());
        $this->assertSame(\implode("\n", \array_slice($source, 1, 10, true)), $r->getSourceSlice());

        // Trims the whitespace line in contents
        $r = new SourceRepresentation(__FILE__, 1, 6);
        $this->assertSame(\array_slice($source, 1, 7, true), $r->getSourceLines());
        $this->assertSame(\implode("\n", \array_slice($source, 1, 7, true)), $r->getSourceSlice());
    }

    /**
     * @covers \Kint\Value\Representation\SourceRepresentation::showFileName
     */
    public function testShowFileName()
    {
        $r = new SourceRepresentation(__FILE__, 1, 7, true);
        $this->assertTrue($r->showFileName());
        $r = new SourceRepresentation(__FILE__, 1, 7, false);
        $this->assertFalse($r->showFileName());
    }

    /**
     * @covers \Kint\Value\Representation\SourceRepresentation::readSource
     */
    public function testNoFile()
    {
        $this->expectException(RuntimeException::class);

        $r = new SourceRepresentation(__FILE__.'/nonexistant', 1);
    }

    /**
     * @covers \Kint\Value\Representation\SourceRepresentation::readSource
     */
    public function testNoLines()
    {
        $this->expectException(RuntimeException::class);

        $r = new SourceRepresentation(__FILE__, 10000, 0);
    }
}
