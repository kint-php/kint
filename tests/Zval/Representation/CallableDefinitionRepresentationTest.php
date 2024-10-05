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

namespace Kint\Test\Zval\Representation;

use Kint\Test\KintTestCase;
use Kint\Zval\Representation\CallableDefinitionRepresentation;

/**
 * @coversNothing
 */
class CallableDefinitionRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Zval\Representation\CallableDefinitionRepresentation::__construct
     */
    public function testConstruct()
    {
        $r = new CallableDefinitionRepresentation('filename', 123, 'classname', 'this is a string');

        $this->assertSame('this is a string', $r->contents);
        $this->assertSame('filename', $r->file);
        $this->assertSame(123, $r->line);
        $this->assertSame('classname', $r->class);
        $this->assertSame('callable_definition', $r->getName());
    }

    public function docstringTrimmedProvider()
    {
        return \array_map(function ($in) {
            return [$in[0], $in[1]];
        }, $this->docstringProvider());
    }

    public function docstringWithoutCommentsProvider()
    {
        return \array_map(function ($in) {
            return [$in[0], $in[2]];
        }, $this->docstringProvider());
    }

    public function docstringOneLineProvider()
    {
        return \array_map(function ($in) {
            return [$in[0], $in[3]];
        }, $this->docstringProvider());
    }

    /**
     * @covers \Kint\Zval\Representation\CallableDefinitionRepresentation::getDocstringTrimmed
     *
     * @dataProvider docstringTrimmedProvider
     */
    public function testGetDocstringTrimmed(string $input, ?string $expect)
    {
        $r = new CallableDefinitionRepresentation('filename', 123, null, $input);

        $this->assertSame($expect, $r->getDocstringTrimmed());
    }

    /**
     * @covers \Kint\Zval\Representation\CallableDefinitionRepresentation::getDocstringWithoutComments
     *
     * @dataProvider docstringWithoutCommentsProvider
     */
    public function testGetDocstringWithoutComments(string $input, ?string $expect)
    {
        $r = new CallableDefinitionRepresentation('filename', 123, null, $input);

        $this->assertSame($expect, $r->getDocstringWithoutComments());
    }

    /**
     * @covers \Kint\Zval\Representation\CallableDefinitionRepresentation::getDocstringOneLine
     *
     * @dataProvider docstringOneLineProvider
     */
    public function testGetDocstringOneLine(string $input, ?string $expect)
    {
        $r = new CallableDefinitionRepresentation('filename', 123, null, $input);

        $this->assertSame($expect, $r->getDocstringOneLine());
    }

    protected function docstringProvider()
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
            ],
            'empty' => [
                '',
                null,
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
            ],
        ];
    }
}
