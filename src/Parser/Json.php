<?php

class Kint_Parser_Json extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        if (KINT_PHP52) {
            return array('string');
        } else {
            return array();
        }
    }

    public function getTriggers()
    {
        if (KINT_PHP52) {
            return Kint_Parser::TRIGGER_SUCCESS;
        } else {
            return Kint_Parser::TRIGGER_NONE;
        }
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (!isset($var[0]) || ($var[0] !== '{' && $var[0] !== '[') || ($json = json_decode($var, true)) === null) {
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

        $r = new Kint_Object_Representation('Json');
        $r->contents = $this->parser->parse($json, $base_obj);

        if (!in_array('depth_limit', $r->contents->hints)) {
            $r->contents = $r->contents->value->contents;
        }

        $o->addRepresentation($r, 0);
    }
}
