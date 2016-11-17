<?php

class Kint_Parser_Plugin_Json extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!is_string($var) || !isset($var[0]) || ($var[0] !== '{' && $var[0] !== '[') || ($json = json_decode($var, true)) === null) {
            return;
        }

        $json = (array) $json;
        if (empty($json)) {
            return;
        }

        $base_obj = new Kint_Object();
        $base_obj->depth = $o->depth;

        if ($o->access_path) {
            $base_obj->access_path = 'json_decode('.$o->access_path.', true)';
        }

        if ($this->parser->max_depth && $base_obj->depth < $this->parser->max_depth) {
            $r = new Kint_Object_Representation('Json');
            $r->contents = $this->parser->parse($json, $base_obj);
            $r->contents = $r->contents->value_representation->contents;

            $o->addRepresentation($r, 0);
        }
    }
}
