<?php

class Kint_Object_Blob extends Kint_Object
{
    /**
     * @var array possible alternative char encodings in order of probability, eg. array('windows-1251')
     */
    public static $char_encodings = array(
        'UTF-8',
        'Windows-1252', // Western; includes iso-8859-1, replace this with windows-1251 if you have Russian code
        'euc-jp',       // Japanese

        // all other charsets cannot be differentiated by PHP and/or are not supported by mb_* functions,
        // I need a better means of detecting the codeset, no idea how though :(

        //		'iso-8859-13',  // Baltic
        //		'windows-1251', // Cyrillic
        //		'windows-1250', // Central European
        //		'shift_jis',    // Japanese
        //		'iso-2022-jp',  // Japanese
    );

    public $type = 'string';
    public $encoding;

    public function renderValueShort()
    {
        if ($rep = $this->value_representation) {
            if (self::strlen($rep->contents) > Kint::$maxStrLength) {
                return self::escape('"'.substr($rep->contents, 0, Kint::$maxStrLength).'...');
            } else {
                return self::escape('"'.$rep->contents.'"');
            }
        }
    }

    public static function strlen($string, $encoding = null)
    {
        if (function_exists('mb_strlen')) {
            if ($encoding === null) {
                $encoding = self::detectEncoding($string);
            }

            return mb_strlen($string, $encoding);
        } else {
            return strlen($string);
        }
    }

    public static function detectEncoding($string)
    {
        if (function_exists('mb_detect_encoding')) {
            $mbDetected = mb_detect_encoding($string);
            if ($mbDetected === 'ASCII') {
                return 'ASCII';
            }
        }

        if (!function_exists('iconv')) {
            return !empty($mbDetected) ? $mbDetected : 'UTF-8';
        }

        $md5 = md5($string);
        foreach (self::$char_encodings as $encoding) {
            // fuck knows why, //IGNORE and //TRANSLIT still throw notice
            if (md5(@iconv($encoding, $encoding, $string)) === $md5) {
                return $encoding;
            }
        }

        return 'ASCII';
    }

    public static function escape($string, $encoding = null)
    {
        if (empty($string)) {
            return $string;
        }

        if (Kint::$enabledMode === Kint::MODE_CLI) {
            return str_replace("\x1b", '\\x1b', $string);
        }

        if (Kint::$enabledMode === Kint::MODE_WHITESPACE) {
            return $string;
        }

        if ($encoding === null) {
            $encoding = self::detectEncoding($string);
        }

        $string = htmlspecialchars($string, ENT_NOQUOTES, $encoding === 'ASCII' ? 'UTF-8' : $encoding);

        if ($encoding === 'UTF-8') {
            // TODO: we could make the symbols hover-title show the code for the invisible symbol
            // when possible force invisible characters to have some sort of display (experimental)
            $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '?', $string);
        }

        // this call converts all non-ASCII characters into html chars of format
        if (function_exists('mb_encode_numericentity')) {
            $string = mb_encode_numericentity(
                $string,
                array(0x80, 0xffff, 0, 0xffff),
                $encoding
            );
        }

        return $string;
    }
}
