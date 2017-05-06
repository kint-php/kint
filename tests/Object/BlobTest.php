<?php

class Kint_Object_BlobTest extends KintTestCase
{
    public function blobProvider()
    {
        $p = new Kint_Parser();
        $b = Kint_Object::blank('$v');

        Kint_Object_Blob::$char_encodings = array(
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
            Kint_Object_Blob::$char_encodings[] = 'SJIS';
            Kint_Object_Blob::$char_encodings[] = 'EUC-JP';
            Kint_Object_Blob::$char_encodings[] = 'Windows-1252';
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
     */
    public function testGetType(Kint_Object_Blob $object, $string, $encoding, $type)
    {
        $this->assertEquals($type, $object->getType());
    }

    /**
     * @dataProvider blobProvider
     */
    public function testGetValueShort(Kint_Object_Blob $object, $string, $encoding)
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

    public function testTransplant()
    {
        $p = new Kint_Parser();
        $b = Kint_Object::blank('$v');

        $string = 'How now brown cow';

        $o = $p->parse($string, clone $b);

        $this->assertNotFalse($o->encoding);
        $this->assertNotNull($o->encoding);
        $this->assertNotEmpty($o->encoding);

        $o2 = $o->transplant(new Kint_Object_Blob());

        $o->hints[] = 'string';

        $this->assertEquals($o, $o2);
        $this->assertNotSame($o, $o2);
        $this->assertSame($o->encoding, $o2->encoding);
    }

    /**
     * @dataProvider blobProvider
     */
    public function testStrlen(Kint_Object_Blob $object, $string, $encoding)
    {
        if ($encoding === false) {
            $this->assertEquals(strlen($string), Kint_Object_Blob::strlen($string));
            $this->assertEquals(strlen($string), Kint_Object_Blob::strlen($string, false));
        } else {
            $this->assertEquals(mb_strlen($string, $encoding), Kint_Object_Blob::strlen($string));
            $this->assertEquals(mb_strlen($string, $encoding), Kint_Object_Blob::strlen($string, $encoding));
        }
    }

    /**
     * @dataProvider blobProvider
     */
    public function testDetectEncoding(Kint_Object_Blob $object, $string, $encoding)
    {
        $this->assertEquals($encoding, Kint_Object_Blob::detectEncoding($string));
    }

    /**
     * @dataProvider blobProvider
     */
    public function testEscapeText(Kint_Object_Blob $object, $string, $encoding)
    {
        Kint::$enabled_mode = Kint::MODE_TEXT;

        $this->assertEquals($string, Kint_Object_Blob::escape($string));
        $this->assertEquals($string, Kint_Object_Blob::escape($string, $encoding));
    }

    /**
     * @dataProvider blobProvider
     */
    public function testEscapeCli(Kint_Object_Blob $object, $string, $encoding)
    {
        Kint::$enabled_mode = Kint::MODE_CLI;

        $this->assertNotContains("\x1b", Kint_Object_Blob::escape($string));
        $this->assertNotContains("\x1b", Kint_Object_Blob::escape($string, $encoding));
    }

    /**
     * @dataProvider blobProvider
     */
    public function testEscapeHtml(Kint_Object_Blob $object, $string, $encoding)
    {
        Kint::$enabled_mode = Kint::MODE_RICH;

        if ($encoding === false || $encoding === 'ASCII') {
            $new_encoding = 'UTF-8';
        } else {
            $new_encoding = $encoding;
        }

        $expect = htmlspecialchars($string, ENT_NOQUOTES, $new_encoding);
        $expect = mb_encode_numericentity($expect, array(0x80, 0xffff, 0, 0xffff), $new_encoding);

        $this->assertEquals($expect, Kint_Object_Blob::escape($string));
        $this->assertEquals($expect, Kint_Object_Blob::escape($string, $encoding));
    }
}
