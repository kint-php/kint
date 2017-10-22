<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\Representation\Representation;
use ReflectionClass;

class ToStringPlugin extends Plugin
{
    public static $blacklist = array(
        'SimpleXMLElement',
    );

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
        $reflection = new ReflectionClass($var);
        if (!$reflection->hasMethod('__toString')) {
            return;
        }

        foreach (self::$blacklist as $class) {
            if ($var instanceof $class) {
                return;
            }
        }

        $r = new Representation('toString');
        $r->contents = (string) $var;

        $o->addRepresentation($r);
    }
}
