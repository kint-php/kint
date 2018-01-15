<?php

namespace Kint\Test\Object;

use Kint\Object\BasicObject;
use Kint\Object\BlobObject;
use Kint\Parser\Parser;
use Kint\Test\KintTestCase;

class BlobObjectTest extends KintTestCase
{
    public function blobProvider()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');

        BlobObject::$char_encodings = array(
            'ASCII',
            'UTF-8',
        );

        $strings = array(
            'empty' => array(
                '',
                'ASCII',
                'string',
            ),
            'ASCII' => array(
                "The quick brown fox jumps<br>\r\n\tover the lazy dog",
                'ASCII',
                'string',
            ),
            'UTF-8' => array(
                "El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso",
                'UTF-8',
                'UTF-8 string',
            ),
            'fail' => array(
                "The quick brown fox jumps<br>\r\n\tover the lazy dog\x90\x1b",
                false,
                'binary string',
            ),
        );

        if (!defined('HHVM_VERSION')) {
            BlobObject::$char_encodings[] = 'SJIS';
            BlobObject::$char_encodings[] = 'EUC-JP';
            BlobObject::$char_encodings[] = 'Windows-1252';
            $strings['SJIS'] = array(
                mb_convert_encoding("キント最強<br>\r\n\tASCII", 'SJIS', 'UTF-8'),
                'SJIS',
                'SJIS string',
            );
            $strings['EUC-JP'] = array(
                mb_convert_encoding("キント最強<br>\r\n\tASCII", 'EUC-JP', 'UTF-8'),
                'EUC-JP',
                'EUC-JP string',
            );
            $strings['yuck'] = array(
                mb_convert_encoding("El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso", 'Windows-1252', 'UTF-8'),
                'Windows-1252',
                'Windows-1252 string',
            );
        }

        foreach ($strings as $encoding => &$string) {
            array_unshift($string, $p->parse($string[0], clone $b));
        }

        return $strings;
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Object\BlobObject::getType
     */
    public function testGetType(BlobObject $object, $string, $encoding, $type)
    {
        $this->assertEquals($type, $object->getType());
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Object\BlobObject::getValueShort
     */
    public function testGetValueShort(BlobObject $object, $string, $encoding)
    {
        if ($encoding) {
            $string = mb_convert_encoding($string, 'UTF-8', $encoding);
            $string = '"'.$string.'"';
            $string = mb_convert_encoding($string, $encoding, 'UTF-8');
        } else {
            $string = '"'.$string.'"';
        }

        $this->assertEquals($string, $object->getValueShort());
    }

    /**
     * @covers \Kint\Object\BlobObject::getValueShort
     */
    public function testNoValueShort()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $s = '';
        $o = $p->parse($s, $b);

        $this->assertEquals('""', $o->getValueShort());
        $o->value = null;
        $this->assertNull($o->getValueShort());
    }

    /**
     * @covers \Kint\Object\BlobObject::transplant
     */
    public function testTransplant()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');

        $string = 'How now brown cow';

        $o = $p->parse($string, clone $b);

        $this->assertInstanceOf('Kint\\Object\\BlobObject', $o);
        if (!$o instanceof BlobObject) {
            return; // phpstan
        }

        $this->assertNotFalse($o->encoding);
        $this->assertNotNull($o->encoding);
        $this->assertNotEmpty($o->encoding);

        $o2 = $o->transplant(new BlobObject());

        $o->hints[] = 'string';

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
        $this->assertSame($o->encoding, $o2->encoding);
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Object\BlobObject::strlen
     */
    public function testStrlen(BlobObject $object, $string, $encoding)
    {
        if ($encoding === false) {
            $this->assertEquals(strlen($string), BlobObject::strlen($string));
            $this->assertEquals(strlen($string), BlobObject::strlen($string, false));
        } else {
            $this->assertEquals(mb_strlen($string, $encoding), BlobObject::strlen($string));
            $this->assertEquals(mb_strlen($string, $encoding), BlobObject::strlen($string, $encoding));
        }
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Object\BlobObject::substr
     */
    public function testSubstr(BlobObject $object, $string, $encoding)
    {
        $length = BlobObject::strlen($string);

        if ($encoding === false) {
            $this->assertEquals(
                substr($string, 1, $length - 1),
                BlobObject::substr($string, 1, $length - 1)
            );
            $this->assertEquals(
                substr($string, 1, $length - 1),
                BlobObject::substr($string, 1, $length - 1, false)
            );
        } else {
            $this->assertEquals(
                mb_substr($string, 1, $length - 1, $encoding),
                BlobObject::substr($string, 1, $length - 1)
            );
            $this->assertEquals(
                mb_substr($string, 1, $length - 1, $encoding),
                BlobObject::substr($string, 1, $length - 1, $encoding)
            );
        }
    }

    /**
     * @dataProvider blobProvider
     * @covers \Kint\Object\BlobObject::detectEncoding
     */
    public function testDetectEncoding(BlobObject $object, $string, $encoding)
    {
        $this->assertEquals($encoding, BlobObject::detectEncoding($string));
    }

    /**
     * @covers \Kint\Object\BlobObject::getType
     * @covers \Kint\Object\BlobObject::getValueShort
     * @covers \Kint\Object\BlobObject::detectEncoding
     */
    public function testWindowsDisabled()
    {
        BlobObject::$char_encodings = array(
            'ASCII',
            'UTF-8',
        );

        $string = mb_convert_encoding("El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso", 'Windows-1252', 'UTF-8');
        $this->assertFalse(BlobObject::detectEncoding($string));

        $p = new Parser();
        $b = BasicObject::blank('$v');
        $o = $p->parse($string, clone $b);

        $this->assertSame('binary string', $o->getType());
        $this->assertSame('"'.$string.'"', $o->getValueShort());
    }
}
