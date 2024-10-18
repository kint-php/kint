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

namespace Kint\Test\Parser;

use DateTime;
use DateTimeImmutable;
use Kint\Parser\DateTimePlugin;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\BadDateTimeClass;
use Kint\Test\KintTestCase;
use Kint\Value\Context\BaseContext;
use Kint\Value\DateTimeValue;
use stdClass;

/**
 * @coversNothing
 */
class DateTimePluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\DateTimePlugin::getTypes
     * @covers \Kint\Parser\DateTimePlugin::getTriggers
     */
    public function testHooks()
    {
        $b = new DateTimePlugin($this->createStub(Parser::class));

        $this->assertSame(['object'], $b->getTypes());
        $this->assertSame(Parser::TRIGGER_SUCCESS, $b->getTriggers());
    }

    public function dateTimeProvider()
    {
        return [
            'dt' => [
                new DateTime('2024-09-28 22:53:30'),
                true,
                '2024-09-28 22:53:30 +00:00 UTC',
            ],
            'dtImmutable' => [
                new DateTimeImmutable('2024-09-28 22:53:30'),
                true,
                '2024-09-28 22:53:30 +00:00 UTC',
            ],
            'tz1' => [
                new DateTime('2024-09-28 22:53:30 +0200'),
                true,
                '2024-09-28 22:53:30 +02:00',
            ],
            'tz2' => [
                new DateTime('2024-09-28 22:53:30 CEST'),
                true,
                '2024-09-28 22:53:30 +02:00 CEST',
            ],
            'tz3' => [
                new DateTime('2024-09-28 22:53:30 Europe/Amsterdam'),
                true,
                '2024-09-28 22:53:30 +02:00 CEST',
            ],
            'microsecond' => [
                new DateTime('2024-09-28 22:53:30.1234'),
                true,
                '2024-09-28 22:53:30.123400 +00:00 UTC',
            ],
            'not_dt' => [
                new stdClass(),
                false,
            ],
            'uncloneable' => [
                new BadDateTimeClass('now'),
                false,
            ],
        ];
    }

    /**
     * @covers \Kint\Parser\DateTimePlugin::parseComplete
     * @covers \Kint\Value\DateTimeValue::getDisplayValue
     *
     * @dataProvider dateTimeProvider
     */
    public function testParse($dt, bool $expectedDt, ?string $expectedShort = null)
    {
        $p = new Parser();
        $dtp = new DateTimePlugin($p);
        $b = new BaseContext('$v');
        $v = $dt;

        $o = $p->parse($v, clone $b);

        $this->assertNotInstanceOf(DateTimeValue::class, $o);

        $p->addPlugin($dtp);
        $o = $p->parse($v, clone $b);

        if (!$expectedDt) {
            $this->assertNotInstanceOf(DateTimeValue::class, $o);
        } else {
            $this->assertInstanceOf(DateTimeValue::class, $o);
            $this->assertSame($expectedShort, $o->getDisplayValue());
        }
    }
}
