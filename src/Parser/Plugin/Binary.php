<?php

class Kint_Parser_Plugin_Binary extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (is_string($var) && !preg_match('/^[\S\n\r\t ]*$/u', $var)) {
            $o->value->hints[] = 'binary';
        }
    }
}
