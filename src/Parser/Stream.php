<?php

class Kint_Parser_Stream extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        return array('resource');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (!$o instanceof Kint_Object_Resource || $o->resource_type !== 'stream') {
            return;
        }

        if (!$meta = stream_get_meta_data($var)) {
            return;
        }

        $rep = new Kint_Object_Representation('Stream');
        $rep->implicit_label = true;

        $base_obj = new Kint_Object();
        $base_obj->depth = $o->depth;

        if ($o->access_path) {
            $base_obj->access_path = 'stream_get_meta_data('.$o->access_path.')';
        }

        $rep->contents = $this->parser->parse($meta, $base_obj);

        if (!in_array('depth_limit', $rep->contents->hints)) {
            $rep->contents = $rep->contents->value->contents;
        }

        $o->addRepresentation($rep, 0);

        $o = $o->transplant(new Kint_Object_Stream($meta));
    }
}
