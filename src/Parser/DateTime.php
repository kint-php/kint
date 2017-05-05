<?php

class Kint_Parser_DateTime extends Kint_Parser_Plugin
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
        if (!$var instanceof DateTime) {
            return;
        }

        $o = $o->transplant(new Kint_Object_DateTime($var));
    }
}
