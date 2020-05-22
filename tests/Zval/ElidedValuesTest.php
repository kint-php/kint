<?php

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

use Kint\Test\KintTestCase;
use Kint\Zval\ElidedValues;

class ElidedValuesTest extends KintTestCase
{
    /**
     * @covers \Kint\Zval\ElidedValues::__construct
     */
    public function testConstruct()
    {
        $x = new ElidedValues(42, 'The meaning of life');

        $this->assertNull($x->value);
        $this->assertSame(42, $x->size);
        $this->assertSame('The meaning of life', $x->description);
        $this->assertContains('elide', $x->hints);
    }

    /**
     * @covers \Kint\Zval\ElidedValues::getAccessPath
     */
    public function testGetAccessPath()
    {
        $x = new ElidedValues(42, 'The meaning of life');
        $x->access_path = 'test path';

        $this->assertNull($x->getAccessPath());
    }
}
