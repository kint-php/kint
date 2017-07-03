<?php

class Kint_Parser_DateTime extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        if (KINT_PHP53) {
            return array('object');
        } else {
            return array();
        }
    }

    public function getTriggers()
    {
        if (KINT_PHP53) {
            return Kint_Parser::TRIGGER_SUCCESS;
        } else {
            return Kint_Parser::TRIGGER_NONE;
        }
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (!$var instanceof DateTime) {
            return;
        }

        $o = $o->transplant(new Kint_Object_DateTime($var));
    }
}
