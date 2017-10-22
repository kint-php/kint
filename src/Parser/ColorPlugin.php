<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\ColorObject;
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

        if (!isset(ColorObject::$color_map[$trimmed]) && !preg_match('/^(?:(?:rgb|hsl)[^\)]{6,}\)|#[0-9a-fA-F]{3,8})$/', $trimmed)) {
            return;
        }

        $rep = new ColorRepresentation($var);

        if ($rep->variant) {
            $o = $o->transplant(new ColorObject($rep));
            $o->removeRepresentation($o->value->name);
            $o->addRepresentation($rep, 0);
        }
    }
}
