<?php

namespace Kint\Object;

class BlobObject extends BasicObject
{
    /**
     * @var array Character encodings to detect
     *
     * @see https://secure.php.net/function.mb-detect-order
     *
     * In practice, mb_detect_encoding can only successfully determine the
     * difference between the following common charsets at once without
     * breaking things for one of the other charsets:
     * - ASCII
     * - UTF-8
     * - SJIS
     * - EUC-JP
     *
     * The order of the charsets is significant. If you put UTF-8 before ASCII
     * it will never match ASCII, because UTF-8 is a superset of ASCII.
     * Similarly, SJIS and EUC-JP frequently match UTF-8 strings, so you should
     * check UTF-8 first. SJIS and EUC-JP seem to work either way, but SJIS is
     * more common so it should probably be first.
     *
     * While you're free to experiment with other charsets, remember to keep
     * this behavior in mind when setting up your char_encodings array.
     *
     * This depends on the mbstring extension
     */
    public static $char_encodings = array(
        'ASCII',
        'UTF-8',
    );

    /**
     * @var array Legacy character encodings to detect
     *
     * @see https://secure.php.net/function.iconv
     *
     * Assuming the other encoding checks fail, this will perform a
     * simple iconv conversion to check for invalid bytes. If any are
     * found it will not match.
     *
     * This can be useful for ambiguous single byte encodings like
     * windows-125x and iso-8859-x which have practically undetectable
     * differences because they use every single byte available.
     *
     * This is *NOT* reliable and should not be trusted implicitly. As
     * with char_encodings, the order of the charsets is significant.
     *
     * This depends on the iconv extension
     */
    public static $legacy_encodings = array();

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

    public function transplant(BasicObject $new)
    {
        $new = parent::transplant($new);
        $new->encoding = $this->encoding;

        return $new;
    }

    public static function strlen($string, $encoding = false)
    {
        if (extension_loaded('mbstring')) {
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
        if (extension_loaded('mbstring')) {
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
        if (extension_loaded('mbstring')) {
            if ($ret = mb_detect_encoding($string, self::$char_encodings, true)) {
                return $ret;
            }
        }

        // Pretty much every character encoding uses first 32 bytes as control
        // characters. If it's not a multi-byte format it's safe to say matching
        // any control character besides tab, nl, and cr means it's binary.
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $string)) {
            return false;
        }

        if (extension_loaded('iconv')) {
            foreach (self::$legacy_encodings as $encoding) {
                if (@iconv($encoding, $encoding, $string) === $string) {
                    return $encoding;
                }
            }
        }

        return false;
    }
}
