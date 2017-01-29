<?php

class Kint_Object_Blob extends Kint_Object
{
    /**
     * @var array Possible char encodings in order of probability
     */
    public static $char_encodings = array(
        'ASCII',
        'UTF-8',
        'Windows-1252', // Western; includes ISO-8859-1
        'EUC-JP', // Japanese

        // all other charsets cannot be differentiated by PHP and/or are not supported by mb_* functions,
        // I need a better means of detecting the codeset, no idea how though :(

        // 'ISO-8859-13', // Baltic
        // 'Windows-1251', // Cyrillic
        // 'Windows-1250', // Central European
        // 'Shift_JIS', // More Japanese
        // 'ISO-2022-jp', // I program like the Japanese make charsets. By trial and error.
    );

    public $type = 'string';
    public $encoding = false;
    public $hints = array('string');

    public function getType()
    {
        if ($this->encoding === false) {
            return 'binary '.$this->type;
        } elseif ($this->encoding === 'ASCII') {
            return $this->type;
        } else {
            return $this->encoding.' '.$this->type;
        }
    }

    public function getValueShort()
    {
        if ($rep = $this->value) {
            return '"'.$rep->contents.'"';
        }
    }

    public function transplant(Kint_Object $new)
    {
        $new = parent::transplant($new);
        $new->encoding = $this->encoding;

        return $new;
    }

    public static function strlen($string, $encoding = false)
    {
        if (function_exists('mb_strlen')) {
            if ($encoding === false) {
                $encoding = self::detectEncoding($string);
            }

            if ($encoding && $encoding !== 'ASCII') {
                return mb_strlen($string, $encoding);
            }
        }

        return strlen($string);
    }

    public static function detectEncoding($string)
    {
        if (function_exists('mb_detect_encoding')) {
            return mb_detect_encoding($string, self::$char_encodings, true);
        }

        if (!function_exists('iconv')) {
            return 'UTF-8';
        }

        $md5 = md5($string);
        foreach (self::$char_encodings as $encoding) {
            // fuck knows why, //IGNORE and //TRANSLIT still throw notice
            if (md5(@iconv($encoding, $encoding, $string)) === $md5) {
                return $encoding;
            }
        }

        return false;
    }

    public static function escape($string, $encoding = false)
    {
        if (empty($string)) {
            return $string;
        }

        if (Kint::$enabled_mode === Kint::MODE_TEXT) {
            return $string;
        }

        if (Kint::$enabled_mode === Kint::MODE_CLI) {
            return str_replace("\x1b", '\\x1b', $string);
        }

        if ($encoding === false) {
            $encoding = self::detectEncoding($string);
        }

        $original_encoding = $encoding;

        if ($encoding === false || $encoding === 'ASCII') {
            $encoding = 'UTF-8';
        }

        $string = htmlspecialchars($string, ENT_NOQUOTES, $encoding);

        // this call converts all non-ASCII characters into numeirc htmlentities
        if ($original_encoding !== 'ASCII' && function_exists('mb_encode_numericentity')) {
            $string = mb_encode_numericentity($string, array(0x80, 0xffff, 0, 0xffff), $encoding);
        }

        return $string;
    }
}
