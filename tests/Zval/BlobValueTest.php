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

namespace Kint\Test\Zval;

use Kint\Parser\Parser;
use Kint\Test\KintTestCase;
use Kint\Utils;
use Kint\Zval\BlobValue;
use Kint\Zval\Context\BaseContext;

/**
 * @coversNothing
 */
class BlobValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Zval\BlobValue::getType
     */
    public function testGetType()
    {
        $v = new BlobValue(new BaseContext('$string'));
        $v->encoding = false;
        $this->assertSame('binary string', $v->getType());
        $v->encoding = 'ASCII';
        $this->assertSame('string', $v->getType());
        $v->encoding = 'UTF-8';
        $this->assertSame('UTF-8 string', $v->getType());
    }

    /**
     * @dataProvider \Kint\Test\UtilsTest::blobProvider
     *
     * @covers \Kint\Zval\BlobValue::getValueShort
     *
     * @param string       $string
     * @param false|string $encoding
     */
    public function testGetValueShort($string, $encoding, array $encodings, array $legacy)
    {
        Utils::$char_encodings = $encodings;
        Utils::$legacy_encodings = $legacy;

        $p = new Parser();
        $base = new BaseContext('$string');

        $object = $p->parse($string, new BaseContext('$string'));

        if ($encoding) {
            $string = \mb_convert_encoding($string, 'UTF-8', $encoding);
            $string = '"'.$string.'"';
            $string = \mb_convert_encoding($string, $encoding, 'UTF-8');
        } else {
            $string = '"'.$string.'"';
        }

        $this->assertSame($string, $object->getValueShort());
    }

    /**
     * @covers \Kint\Zval\BlobValue::getValueShort
     */
    public function testNoValueShort()
    {
        $p = new Parser();
        $b = new BaseContext('$s');
        $s = '';
        $o = $p->parse($s, $b);

        $this->assertSame('""', $o->getValueShort());
        $o->value = null;
        $this->assertNull($o->getValueShort());
    }

    /**
     * @covers \Kint\Zval\BlobValue::transplant
     */
    public function testTransplant()
    {
        $p = new Parser();
        $b = new BaseContext('$string');

        $string = 'How now brown cow';

        $o = $p->parse($string, clone $b);

        $this->assertInstanceOf(BlobValue::class, $o);

        $this->assertNotFalse($o->encoding);
        $this->assertNotNull($o->encoding);
        $this->assertNotEmpty($o->encoding);

        $o2 = new BlobValue($o->getContext());
        $o2->transplant($o);

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
        $this->assertSame($o->encoding, $o2->encoding);
    }
}
