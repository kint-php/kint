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

namespace Kint\Test\Object\Representation;

use Kint\Object\Representation\MicrotimeRepresentation;
use Kint\Test\KintTestCase;

class MicrotimeRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Object\Representation\MicrotimeRepresentation::__construct
     */
    public function testConstruct()
    {
        $r = new MicrotimeRepresentation(123, 456, 7, 8, 100);

        $mem = \memory_get_usage();
        $mem_peak = \memory_get_peak_usage();

        $this->assertSame(123, $r->seconds);
        $this->assertSame(456, $r->microseconds);
        $this->assertSame(7, $r->group);
        $this->assertSame(8, $r->lap);
        $this->assertSame(100, $r->total);
        $this->assertSame(0, $r->i);
        $this->assertNull($r->avg);

        $r = new MicrotimeRepresentation(123, 456, 7, 8, 100, 5);
        $this->assertSame(5, $r->i);
        $this->assertSame(20, $r->avg);

        // PHP 5.3 needs a leeway of 3k, 5.4+ needs 2k.
        // At some point in the 7.x range they become exactly equal.
        $this->assertLessThan(3000, \abs($mem - $r->mem));
        $this->assertSame($mem_peak, $r->mem_peak);
    }

    /**
     * @covers \Kint\Object\Representation\MicrotimeRepresentation::getDateTime
     */
    public function testGetDateTime()
    {
        $r = new MicrotimeRepresentation(1234, 5678, 0);
        $dt2 = $r->getDateTime();
        $this->assertSame('1234.005678', $dt2->format('U.u'));

        $r = new MicrotimeRepresentation(1234, 0, 0);
        $dt2 = $r->getDateTime();
        $this->assertSame('1234.000000', $dt2->format('U.u'));
    }
}
