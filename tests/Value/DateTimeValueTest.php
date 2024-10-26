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

use DateTime;
use Error;
use Kint\Test\Fixtures\BadDateTimeClass;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\DateTimeValue;

/**
 * @coversNothing
 */
class DateTimeValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\DateTimeValue::__construct
     * @covers \Kint\Value\DateTimeValue::getDisplayValue
     */
    public function testConstruct()
    {
        $dt = new DateTime('2024-09-28 22:53:30.1234 CEST');

        $v = new DateTimeValue($b = new BaseContext('name'), $dt);

        $this->assertSame('2024-09-28 22:53:30.123400 +02:00 CEST', $v->getDisplayValue());
        $this->assertSame($b, $v->getContext());
        $this->assertSame(DateTime::class, $v->getClassName());
        $this->assertSame(\spl_object_hash($dt), $v->getSplObjectHash());
        $this->assertSame(\spl_object_id($dt), $v->getSplObjectId());
    }

    /**
     * @covers \Kint\Value\DateTimeValue::__construct
     */
    public function testBadConstruct()
    {
        $dt = new BadDateTimeClass();

        $this->expectException(Error::class);

        $v = new DateTimeValue(new BaseContext('name'), $dt);
    }

    /**
     * @covers \Kint\Value\DateTimeValue::getHint
     */
    public function testGetHint()
    {
        $v = new DateTimeValue(new BaseContext('name'), new DateTime());

        $this->assertSame('datetime', $v->getHint());

        $v->flags |= AbstractValue::FLAG_DEPTH_LIMIT;

        $this->assertSame('depth_limit', $v->getHint());
    }
}
