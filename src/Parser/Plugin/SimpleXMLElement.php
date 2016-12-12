<?php

class Kint_Parser_Plugin_SimpleXMLElement extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!is_object($var) || !($var instanceof SimpleXMLElement) || !$this->parseChildren($o)) {
            return;
        }

        $a = new Kint_Object_Representation('Attributes');

        $base_obj = new Kint_Object();
        $base_obj->depth = $o->depth;

        if ($o->access_path) {
            $base_obj->access_path = '(string) '.$o->access_path;
        }

        $attribs = iterator_to_array($var->attributes());
        $attribs = array_map('strval', $attribs);

        // XML attributes are by definition strings and don't have children,
        // so up the depth limit in case we're just below the limit since
        // there won't be any recursive stuff anyway.
        $depth_stash = $this->parser->max_depth;
        $this->parser->max_depth = 0;
        $a->contents = $this->parser->parse($attribs, $base_obj);
        $this->parser->max_depth = $depth_stash;

        $a->contents = $a->contents->value->contents;

        $o->addRepresentation($a, 0);

        if ($o->value) {
            $c = new Kint_Object_Representation('Children');

            foreach ($o->value->contents as $value) {
                if ($value->name !== '@attributes') {
                    $c->contents[] = $value;
                }

                if ($value->type === 'string') {
                    $value->access_path = '(string) '.$value->access_path;
                }

                if ($value->type === 'array') {
                    $value->access_path = null;
                }
            }

            $o->size = count($c->contents);

            if (!$o->size) {
                $o->size = null;
            }

            $o->addRepresentation($c, 0);
        }

        $o->removeRepresentation('properties');
        $o->removeRepresentation('iterator');
    }
}
