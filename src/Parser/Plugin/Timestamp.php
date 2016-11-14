<?php

class Kint_Parser_Plugin_Timestamp extends Kint_Parser_Plugin
{
    public static $blacklist = array(
        (1 << 31) - 1,
        1 << 31,
        (1 << 32) - 1,
        1 << 32,
    );

    public function parse(&$var, Kint_Object &$o)
    {
        if (!ctype_digit($var) && !is_int($var)) {
            return;
        }

        if (in_array($var, self::$blacklist)) {
            return;
        }

        $len = strlen($var);

        // Guess for anything between March 1973 and November 2286
        if ($len === 9 || $len === 10) {
            // If it's an int or string that's this short it probably has no other meaning
            // Additionally it's highly unlikely the shortValue will be clipped for length
            // If you're writing a plugin that interferes with this, just put your
            // parser plugin further down the list so that it gets loaded afterwards.
            $o->value_representation->label = 'Timestamp';
            $o->value_representation->hints[] = 'timestamp';
        }
    }
}
