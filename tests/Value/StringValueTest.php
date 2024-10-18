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

use DomainException;
use Kint\Parser\Parser;
use Kint\Test\KintTestCase;
use Kint\Utils;
use Kint\Value\Context\BaseContext;
use Kint\Value\StringValue;

/**
 * @coversNothing
 */
class StringValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\StringValue::__construct
     */
    public function testConstruct()
    {
        $v = new StringValue(new BaseContext('$string'), 'test', 'ASCII');
        $this->assertSame('string', $v->getType());
        $this->assertSame('test', $v->getValue());
        $this->assertSame(4, $v->getLength());
        $this->assertSame('ASCII', $v->getEncoding());
    }

    /**
     * @covers \Kint\Value\StringValue::getValue
     */
    public function testGetValue()
    {
        $v = new StringValue(new BaseContext('$string'), 'test', false);
        $this->assertSame('test', $v->getValue());
        $v = new StringValue(new BaseContext('$string'), 'test my stuff', 'ASCII');
        $this->assertSame('test my stuff', $v->getValue());

        $sjis = \mb_convert_encoding('キンと', 'SJIS', 'UTF-8');
        $v = new StringValue(new BaseContext('$string'), $sjis, 'SJIS');
        $this->assertSame($sjis, $v->getValue());
    }

    /**
     * @covers \Kint\Value\StringValue::getValueUtf8
     */
    public function testGetValueUtf8()
    {
        $v = new StringValue(new BaseContext('$string'), 'test my stuff', 'ASCII');
        $this->assertSame('test my stuff', $v->getValueUtf8());

        $sjis = \mb_convert_encoding('キンと', 'SJIS', 'UTF-8');
        $v = new StringValue(new BaseContext('$string'), $sjis, 'SJIS');
        $this->assertSame('キンと', $v->getValueUtf8());
        $this->assertNotSame($v->getValue(), $v->getValueUtf8());

        $this->expectException(DomainException::class);
        $v = new StringValue(new BaseContext('$string'), 'test', false);
        $v->getValueUtf8();
    }

    /**
     * @covers \Kint\Value\StringValue::getLength
     */
    public function testGetLength()
    {
        $v = new StringValue(new BaseContext('$string'), 'test', false);
        $this->assertSame(4, $v->getLength());
        $v = new StringValue(new BaseContext('$string'), 'test my stuff', 'ASCII');
        $this->assertSame(13, $v->getLength());
    }

    /**
     * @covers \Kint\Value\StringValue::getEncoding
     */
    public function testGetEncoding()
    {
        $v = new StringValue(new BaseContext('$string'), 'test', false);
        $this->assertFalse($v->getEncoding());
        $v = new StringValue(new BaseContext('$string'), 'test my stuff', 'ASCII');
        $this->assertSame('ASCII', $v->getEncoding());
        $v = new StringValue(new BaseContext('$string'), 'test my stuff', 'UTF-8');
        $this->assertSame('UTF-8', $v->getEncoding());
    }

    /**
     * @covers \Kint\Value\StringValue::getDisplayType
     */
    public function testGetDisplayType()
    {
        $v = new StringValue(new BaseContext('$string'), 'test', false);
        $this->assertSame('binary string', $v->getDisplayType());
        $v = new StringValue(new BaseContext('$string'), 'test', 'ASCII');
        $this->assertSame('string', $v->getDisplayType());
        $v = new StringValue(new BaseContext('$string'), 'test', 'UTF-8');
        $this->assertSame('UTF-8 string', $v->getDisplayType());
    }

    /**
     * @covers \Kint\Value\StringValue::getDisplaySize
     */
    public function testGetDisplaySize()
    {
        $v = new StringValue(new BaseContext('$string'), 'test', false);
        $this->assertSame('4', $v->getDisplaySize());
        $v = new StringValue(new BaseContext('$string'), 'test my stuff', 'ASCII');
        $this->assertSame('13', $v->getDisplaySize());
    }

    /**
     * @dataProvider \Kint\Test\UtilsTest::blobProvider
     *
     * @covers \Kint\Value\StringValue::getDisplayValue
     */
    public function testGetDisplayValue(string $string, $encoding, array $encodings, array $legacy)
    {
        Utils::$char_encodings = $encodings;
        Utils::$legacy_encodings = $legacy;

        $p = new Parser();
        $base = new BaseContext('$string');

        $v = $p->parse($string, new BaseContext('$string'));

        if ($encoding) {
            $string = \mb_convert_encoding($string, 'UTF-8', $encoding);
            $string = '"'.$string.'"';
            $string = \mb_convert_encoding($string, $encoding, 'UTF-8');
        } else {
            $string = '"'.$string.'"';
        }

        $this->assertSame($string, $v->getDisplayValue());
    }

    /**
     * @covers \Kint\Value\StringValue::getDisplayValue
     */
    public function testEmptyDisplayValue()
    {
        $base = new BaseContext('$string');
        $v = new StringValue($base, '');

        $this->assertSame('""', $v->getDisplayValue());
    }
}
