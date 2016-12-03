<?php

class Kint_Parser_Plugin_Base64 extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!is_string($var) || !preg_match('%^(?:[A-Za-z0-9+/=]{4})+$%', $var)) {
            return;
        }

        $data = base64_decode($var);

        if ($data === false) {
            return;
        }

        $base_obj = new Kint_Object();
        $base_obj->depth = $o->depth + 1;
        $base_obj->name = 'base64_decode('.$o->name.')';

        if ($o->access_path) {
            $base_obj->access_path = 'base64_decode('.$o->access_path.')';
        }

        $r = new Kint_Object_Representation('Base64');
        $r->contents = $this->parser->parse($data, $base_obj);

        $o->addRepresentation($r, 0);
    }
}
