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
use Kint\Value\Representation\CallableDefinitionRepresentation;

/**
 * @coversNothing
 */
class CallableDefinitionRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\Representation\CallableDefinitionRepresentation::__construct
     * @covers \Kint\Value\Representation\CallableDefinitionRepresentation::getFileName
     * @covers \Kint\Value\Representation\CallableDefinitionRepresentation::getLine
     * @covers \Kint\Value\Representation\CallableDefinitionRepresentation::getHint
     * @covers \Kint\Value\Representation\CallableDefinitionRepresentation::getDocstring
     */
    public function testConstruct()
    {
        $r = new CallableDefinitionRepresentation('filename', 123, '/** this is a docstring */');

        $this->assertSame('/** this is a docstring */', $r->getDocstring());
        $this->assertSame('filename', $r->getFileName());
        $this->assertSame(123, $r->getLine());
        $this->assertSame('callable_definition', $r->getName());
        $this->assertSame('callable', $r->getHint());
    }

    public function docstringTrimmedProvider()
    {
        return \array_map(function ($in) {
            return [$in[0], $in[1], $in[4]];
        }, $this->docstringProvider());
    }

    public function docstringWithoutCommentsProvider()
    {
        return \array_map(function ($in) {
            return [$in[0], $in[2], $in[4]];
        }, $this->docstringProvider());
    }

    public function docstringOneLineProvider()
    {
        return \array_map(function ($in) {
            return [$in[0], $in[3], $in[4]];
        }, $this->docstringProvider());
    }

    /**
     * @covers \Kint\Value\Representation\CallableDefinitionRepresentation::getDocstringTrimmed
     *
     * @dataProvider docstringTrimmedProvider
     */
    public function testGetDocstringTrimmed(?string $input, ?string $expect, ?string $expect_exception = null)
    {
        if ($expect_exception) {
            $this->expectException($expect_exception);
        }

        $r = new CallableDefinitionRepresentation('filename', 123, $input);

        $this->assertSame($expect, $r->getDocstringTrimmed());
    }

    /**
     * @covers \Kint\Value\Representation\CallableDefinitionRepresentation::getDocstringWithoutComments
     *
     * @dataProvider docstringWithoutCommentsProvider
     */
    public function testGetDocstringWithoutComments(?string $input, ?string $expect, ?string $expect_exception = null)
    {
        if ($expect_exception) {
            $this->expectException($expect_exception);
        }

        $r = new CallableDefinitionRepresentation('filename', 123, $input);

        $this->assertSame($expect, $r->getDocstringWithoutComments());
    }

    /**
     * @covers \Kint\Value\Representation\CallableDefinitionRepresentation::getDocstringFirstLine
     *
     * @dataProvider docstringOneLineProvider
     */
    public function testGetDocstringFirstLine(?string $input, ?string $expect, ?string $expect_exception = null)
    {
        if ($expect_exception) {
            $this->expectException($expect_exception);
        }

        $r = new CallableDefinitionRepresentation('filename', 123, $input);

        $this->assertSame($expect, $r->getDocstringFirstLine());
    }

    /**
     * @covers \Kint\Value\Representation\CallableDefinitionRepresentation::__construct
     */
    public function testInvalidDocstring()
    {
        $this->expectException(InvalidArgumentException::class);

        $r = new CallableDefinitionRepresentation('filename', 123, "It's-a me, mario!");
    }

    public function docstringProvider()
    {
        return [
            'single line' => [
                '/**
                  * @return wat
                  */',
                <<<END
                /**
                 * @return wat
                 */
                END,
                '@return wat',
                null,
                null,
            ],
            'bullets' => [
                '/**
                  * * This is an item
                  * * This is another item
                  */',
                <<<END
                /**
                 * * This is an item
                 * * This is another item
                 */
                END,
                "* This is an item\n* This is another item",
                '* This is an item * This is another item',
                null,
            ],
            'full' => [
                '/**
                  * This function does a thing
                  *
                  * It does it really well because I wrote it. It couldn\'t possibly go wrong.
                  *
                  * @return OhShitItBlewUp
                  */',
                <<<END
                /**
                 * This function does a thing
                 *
                 * It does it really well because I wrote it. It couldn't possibly go wrong.
                 *
                 * @return OhShitItBlewUp
                 */
                END,
                "This function does a thing\n\nIt does it really well because I wrote it. It couldn't possibly go wrong.\n\n@return OhShitItBlewUp",
                'This function does a thing',
                null,
            ],
            'null' => [
                null,
                null,
                null,
                null,
                null,
            ],
        ];
    }
}
