<?php

class Kint_Parser_ToString extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        $reflection = new ReflectionClass($var);
        if (!$reflection->hasMethod('__toString')) {
            return;
        }

        $r = new Kint_Object_Representation('toString');
        $r->contents = (string) $var;

        $o->addRepresentation($r);
    }
}
