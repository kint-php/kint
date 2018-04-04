<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\Representation\ColorRepresentation;

class ColorPlugin extends Plugin
{
    public function getTypes()
    {
        return array('string');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        if (strlen($var) > 32) {
            return;
        }

        $trimmed = strtolower(trim($var));

        if (!isset(ColorRepresentation::$color_map[$trimmed]) && !preg_match('/^(?:(?:rgb|hsl)[^\)]{6,}\)|#[0-9a-fA-F]{3,8})$/', $trimmed)) {
            return;
        }

        $rep = new ColorRepresentation($var);

        if ($rep->variant) {
            $o->removeRepresentation($o->value);
            $o->addRepresentation($rep, 0);
            $o->hints[] = 'color';
        }
    }
}
