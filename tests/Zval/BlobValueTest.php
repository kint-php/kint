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

use Kint\Parser\Parser;
use Kint\Test\KintTestCase;
use Kint\Zval\BlobValue;
use Kint\Zval\Value;

class BlobValueTest extends KintTestCase
{
    public function blobProvider()
    {
        $encodings = [
            'ASCII',
            'UTF-8',
            'SJIS',
            'EUC-JP',
        ];

        $legacy = [
            'Windows-1252',
            'Windows-1251',
        ];

        $strings = [
            'empty' => [
                '',
                'ASCII',
                'string',
            ],
            'ASCII' => [
                "The quick brown fox jumps<br>\r\n\tover the lazy dog",
                'ASCII',
                'string',
            ],
            'UTF-8' => [
                "El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso",
                'UTF-8',
                'UTF-8 string',
            ],
            'SJIS' => [
                \mb_convert_encoding("キント最強<br>\r\n\tASCII", 'SJIS', 'UTF-8'),
                'SJIS',
                'SJIS string',
            ],
            'EUC-JP' => [
                \mb_convert_encoding("キント最強<br>\r\n\tASCII", 'EUC-JP', 'UTF-8'),
                'EUC-JP',
                'EUC-JP string',
            ],
            'yuck' => [
                \mb_convert_encoding("El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso", 'Windows-1252', 'UTF-8'),
                'Windows-1252',
                'Windows-1252 string',
            ],
            'also yuck' => [
                \mb_convert_encoding('This here cyrillic привет Ќ', 'Windows-1251', 'UTF-8'),
                'Windows-1251',
                'Windows-1251 string',
            ],
            'fail' => [
                "The quick brown fox jumps<br>\r\n\tover the lazy dog\x90\x1b",
                false,
                'binary string',
            ],
        ];

        foreach ($strings as $encoding => &$string) {
            $string[] = $encodings;
            $string[] = $legacy;
        }

        return $strings;
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Zval\BlobValue::detectEncoding
     *
     * @param string       $string
     * @param false|string $encoding
     * @param string       $type
     */
    public function testDetectEncoding($string, $encoding, $type, array $encodings, array $legacy)
    {
        BlobValue::$char_encodings = $encodings;
        BlobValue::$legacy_encodings = $legacy;

        $this->assertSame($encoding, BlobValue::detectEncoding($string));
    }

    public function testDetectLegacyEncoding()
    {
        BlobValue::$legacy_encodings = [
            'Windows-1252',
            'Windows-1251',
        ];
        $string = \mb_convert_encoding(
            "El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso",
            'Windows-1252',
            'UTF-8'
        );
        $this->assertSame('Windows-1252', BlobValue::detectEncoding($string));

        BlobValue::$legacy_encodings = [
            'Windows-1251',
            'Windows-1252',
        ];
        $string = \mb_convert_encoding(
            'привет',
            'Windows-1251',
            'UTF-8'
        );
        $this->assertSame('Windows-1251', BlobValue::detectEncoding($string));

        // Yes. This is as good as it gets with those old poorly-engineered encodings. USE UTF-8!
        BlobValue::$legacy_encodings = [
            'Windows-1252',
            'Windows-1251',
        ];
        $this->assertSame('Windows-1252', BlobValue::detectEncoding($string));

        $string = \mb_convert_encoding(
            'привет Ќ',
            'Windows-1251',
            'UTF-8'
        );
        $this->assertSame('Windows-1251', BlobValue::detectEncoding($string));
    }

    /**
     * @covers \Kint\Zval\BlobValue::detectEncoding
     * @covers \Kint\Zval\BlobValue::getType
     * @covers \Kint\Zval\BlobValue::getValueShort
     */
    public function testDetectLegacyEncodingDisabled()
    {
        BlobValue::$char_encodings = [
            'ASCII',
            'UTF-8',
        ];

        $string = \mb_convert_encoding("El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso", 'Windows-1252', 'UTF-8');
        $this->assertFalse(BlobValue::detectEncoding($string));

        $p = new Parser();
        $b = Value::blank();
        $o = $p->parse($string, clone $b);

        $this->assertSame('binary string', $o->getType());
        $this->assertSame('"'.$string.'"', $o->getValueShort());
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Zval\BlobValue::getType
     *
     * @param string       $string
     * @param false|string $encoding
     * @param string       $type
     */
    public function testGetType($string, $encoding, $type, array $encodings, array $legacy)
    {
        BlobValue::$char_encodings = $encodings;
        BlobValue::$legacy_encodings = $legacy;

        $p = new Parser();

        $object = $p->parse($string, Value::blank());

        $this->assertSame($type, $object->getType());
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Zval\BlobValue::getValueShort
     *
     * @param string       $string
     * @param false|string $encoding
     * @param string       $type
     */
    public function testGetValueShort($string, $encoding, $type, array $encodings, array $legacy)
    {
        BlobValue::$char_encodings = $encodings;
        BlobValue::$legacy_encodings = $legacy;

        $p = new Parser();

        $object = $p->parse($string, Value::blank());

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
        $b = Value::blank();
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
        $b = Value::blank();

        $string = 'How now brown cow';

        $o = $p->parse($string, clone $b);

        $this->assertInstanceOf('Kint\\Zval\\BlobValue', $o);

        $this->assertNotFalse($o->encoding);
        $this->assertNotNull($o->encoding);
        $this->assertNotEmpty($o->encoding);

        $o2 = new BlobValue();
        $o2->transplant($o);

        $o->hints[] = 'string';

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
        $this->assertSame($o->encoding, $o2->encoding);
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Zval\BlobValue::strlen
     *
     * @param string       $string
     * @param false|string $encoding
     * @param string       $type
     */
    public function testStrlen($string, $encoding, $type, array $encodings, array $legacy)
    {
        BlobValue::$char_encodings = $encodings;
        BlobValue::$legacy_encodings = $legacy;

        if (false === $encoding) {
            $this->assertSame(\strlen($string), BlobValue::strlen($string));
            $this->assertSame(\strlen($string), BlobValue::strlen($string, false));
        } else {
            $this->assertSame(\mb_strlen($string, $encoding), BlobValue::strlen($string));
            $this->assertSame(\mb_strlen($string, $encoding), BlobValue::strlen($string, $encoding));
        }
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Zval\BlobValue::substr
     *
     * @param string       $string
     * @param false|string $encoding
     * @param string       $type
     */
    public function testSubstr($string, $encoding, $type, array $encodings, array $legacy)
    {
        BlobValue::$char_encodings = $encodings;
        BlobValue::$legacy_encodings = $legacy;

        $length = BlobValue::strlen($string);

        if (false === $encoding) {
            $this->assertSame(
                \substr($string, 1, $length - 1),
                BlobValue::substr($string, 1, $length - 1)
            );
            $this->assertSame(
                \substr($string, 1, $length - 1),
                BlobValue::substr($string, 1, $length - 1, false)
            );
        } else {
            $this->assertSame(
                \mb_substr($string, 1, $length - 1, $encoding),
                BlobValue::substr($string, 1, $length - 1)
            );
            $this->assertSame(
                \mb_substr($string, 1, $length - 1, $encoding),
                BlobValue::substr($string, 1, $length - 1, $encoding)
            );
        }
    }
}
