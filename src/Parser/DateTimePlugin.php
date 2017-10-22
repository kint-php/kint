<?php

namespace Kint\Parser;

use DateTime;
use Kint\Object\BasicObject;
use Kint\Object\DateTimeObject;

class DateTimePlugin extends Plugin
{
    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        if (!$var instanceof DateTime) {
            return;
        }

        $o = $o->transplant(new DateTimeObject($var));
    }
}
