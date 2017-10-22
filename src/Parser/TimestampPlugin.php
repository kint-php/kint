<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;

class TimestampPlugin extends Plugin
{
    public static $blacklist = array(
        2147483648,
        2147483647,
        1073741824,
        1073741823,
    );

    public function getTypes()
    {
        return array('string', 'integer');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        if (is_string($var) && !ctype_digit($var)) {
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
            $o->value->label = 'Timestamp';
            $o->value->hints[] = 'timestamp';
        }
    }
}
