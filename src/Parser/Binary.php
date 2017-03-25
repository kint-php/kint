<?php

class Kint_Parser_Binary extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        return array('string');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (!preg_match('/^[\S\n\r\t ]*$/u', $var)) {
            $o->value->hints[] = 'binary';
        }
    }
}
