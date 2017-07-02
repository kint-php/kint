<?php

class Kint_Object_Blob extends Kint_Object
{
    /**
     * @var array Character encodings to detect
     *
     * @see http://php.net/manual/en/function.mb-detect-order.php
     *
     * In practice, mb_detect_encoding can only successfully determine the
     * difference between the following common charsets at once without
     * breaking things for one of the other charsets:
     * - ASCII
     * - UTF-8
     * - SJIS
     * - EUC-JP
     *
     * If the array contains 'Windows-1252' special checking will be done
     * *after* all other encodings have failed. (Since it's likely to match
     * almost anything)
     *
     * The order of the charsets is significant. If you put UTF-8 before ASCII
     * it will never match ASCII, because UTF-8 is a superset of ASCII.
     * Similarly, SJIS and EUC-JP frequently match UTF-8 strings, so you should
     * check UTF-8 first. SJIS and EUC-JP seem to work either way, but SJIS is
     * more common so it should probably be first.
     *
     * Keep this behavior in mind when setting up your char_encodings array.
     *
     * Note that HHVM doesn't support SJIS or EUC-JP making them moot.
     */
    public static $char_encodings = array(
        'ASCII',
        'UTF-8',
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

    public static function substr($string, $start, $length = null, $encoding = false)
    {
        if (function_exists('mb_substr')) {
            if ($encoding === false) {
                $encoding = self::detectEncoding($string);
            }

            if ($encoding && $encoding !== 'ASCII') {
                return mb_substr($string, $start, $length, $encoding);
            }
        }

        return substr($string, $start, isset($length) ? $length : PHP_INT_MAX);
    }

    public static function detectEncoding($string)
    {
        if (function_exists('mb_detect_encoding')) {
            if ($ret = mb_detect_encoding($string, array_diff(self::$char_encodings, array('Windows-1252')), true)) {
                return $ret;
            } elseif (!in_array('Windows-1252', self::$char_encodings) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x81\x8D\x8F\x90\x9D]/', $string)) {
                return false;
            } else {
                return 'Windows-1252';
            }
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
        static $show_dep = true;

        if ($show_dep) {
            trigger_error('Kint_Object_Blob::escape() is deprecated and will be removed in Kint 3.0. Use renderer-specific escape methods instead.', KINT_PHP53 ? E_USER_DEPRECATED : E_USER_NOTICE);
            $show_dep = false;
        }

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
