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
use Kint\Value\Representation\MicrotimeRepresentation;

/**
 * @coversNothing
 */
class MicrotimeRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::__construct
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getGroup
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getLapTime
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getTotalTime
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getAverageTime
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getMemoryUsage
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getMemoryUsageReal
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getMemoryPeakUsage
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getMemoryPeakUsageReal
     */
    public function testConstruct()
    {
        $r = new MicrotimeRepresentation(123, 456, 'hello', 8, 100);

        $this->assertSame(123, $r->getDateTime()->getTimestamp());
        $this->assertSame(456, (int) $r->getDateTime()->format('u'));
        $this->assertSame('hello', $r->getGroup());
        $this->assertSame(8.0, $r->getLapTime());
        $this->assertSame(100.0, $r->getTotalTime());
        $this->assertNull($r->getAverageTime());

        $r = new MicrotimeRepresentation(123, 456, 'hello', 8, 100, 5);

        $mem = \memory_get_usage();
        $mem_real = \memory_get_usage(true);
        $mem_peak = \memory_get_peak_usage();
        $mem_peak_real = \memory_get_peak_usage(true);

        $this->assertSame(20.0, $r->getAverageTime());

        // Memory usage isn't going to be identical after making the representation
        $this->assertLessThan(1024, \abs($mem - $r->getMemoryUsage()));
        $this->assertSame($mem_real, $r->getMemoryUsageReal());
        $this->assertSame($mem_peak, $r->getMemoryPeakUsage());
        $this->assertSame($mem_peak_real, $r->getMemoryPeakUsageReal());
    }

    /**
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getHint
     */
    public function testGetHint()
    {
        $r = new MicrotimeRepresentation(1234, 5678, 'world');
        $this->assertSame('microtime', $r->getHint());
    }

    /**
     * @covers \Kint\Value\Representation\MicrotimeRepresentation::getDateTime
     */
    public function testGetDateTime()
    {
        $r = new MicrotimeRepresentation(1234, 5678, 'world');
        $dt2 = $r->getDateTime();
        $this->assertSame('1234.005678', $dt2->format('U.u'));

        $r = new MicrotimeRepresentation(1234, 0, 'world');
        $dt2 = $r->getDateTime();
        $this->assertSame('1234.000000', $dt2->format('U.u'));
    }
}
