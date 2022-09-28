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
use Kint\Zval\Representation\MethodDefinitionRepresentation;

/**
 * @coversNothing
 */
class MethodDefinitionRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Zval\Representation\MethodDefinitionRepresentation::__construct
     */
    public function testConstruct()
    {
        $r = new MethodDefinitionRepresentation('filename', 123, 'classname', 'this is a string');

        $this->assertSame('this is a string', $r->contents);
        $this->assertSame('filename', $r->file);
        $this->assertSame(123, $r->line);
        $this->assertSame('classname', $r->class);
        $this->assertSame('method_definition', $r->getName());
    }

    public function docstringProvider()
    {
        return [
            'single line' => [
                '/**
                  * @return wat
                  */',
                '@return wat',
            ],
            'empty' => [
                '',
                null,
            ],
            'bullets' => [
                '/**
                  * * This is an item
                  * * This is another item
                  */',
                "* This is an item\n* This is another item",
            ],
        ];
    }

    /**
     * @covers \Kint\Zval\Representation\MethodDefinitionRepresentation::getDocstringWithoutComments
     *
     * @dataProvider docstringProvider
     *
     * @param string      $input
     * @param null|string $expect
     */
    public function testGetDocstringWithoutComments($input, $expect)
    {
        $r = new MethodDefinitionRepresentation(null, null, null, $input);

        $this->assertSame($expect, $r->getDocstringWithoutComments());
    }
}
