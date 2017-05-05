<?php

class Kint_Parser_Throwable extends Kint_Parser_Plugin
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
        if (!$var instanceof Exception && (!KINT_PHP70 || !$var instanceof Throwable)) {
            return;
        }

        $o = $o->transplant(new Kint_Object_Throwable($var));
    }
}
