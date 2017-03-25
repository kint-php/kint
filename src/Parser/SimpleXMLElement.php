<?php

class Kint_Parser_SimpleXMLElement extends Kint_Parser_Plugin
{
    /**
     * Show all properties and methods.
     *
     * @var bool
     */
    public static $verbose = false;

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
        if (!$var instanceof SimpleXMLElement) {
            return;
        }

        $o->hints[] = 'simplexml_element';

        if (!self::$verbose) {
            $o->removeRepresentation('properties');
            $o->removeRepresentation('iterator');
            $o->removeRepresentation('methods');
        }

        // Attributes
        $a = new Kint_Object_Representation('Attributes');

        $base_obj = new Kint_Object();
        $base_obj->depth = $o->depth;

        if ($o->access_path) {
            $base_obj->access_path = '(string) '.$o->access_path;
        }

        if ($var->attributes()) {
            $attribs = iterator_to_array($var->attributes());
            $attribs = array_map('strval', $attribs);
        } else {
            $attribs = array();
        }

        // XML attributes are by definition strings and don't have children,
        // so up the depth limit in case we're just below the limit since
        // there won't be any recursive stuff anyway.
        $depth_stash = $this->parser->max_depth;
        $this->parser->max_depth = 0;
        $a->contents = $this->parser->parse($attribs, $base_obj);
        $this->parser->max_depth = $depth_stash;

        $a->contents = $a->contents->value->contents;

        $o->addRepresentation($a, 0);

        // Children
        // We need to check children() separately from the values we already parsed because
        // text contents won't show up in children() but they will show up in properties.
        //
        // Why do we still need to check for attributes if we already have an attributes()
        // method? Hell if I know!
        $children = $var->children();

        if ($o->value) {
            $c = new Kint_Object_Representation('Children');

            foreach ($o->value->contents as $value) {
                if ($value->name === '@attributes') {
                    continue;
                } elseif (isset($children->{$value->name})) {
                    $i = 0;

                    while (isset($children->{$value->name}[$i])) {
                        $base_obj = new Kint_Object();
                        $base_obj->depth = $o->depth + 1;
                        $base_obj->name = $value->name;
                        if ($value->access_path) {
                            $base_obj->access_path = $value->access_path.'['.$i.']';
                        }

                        $value = $this->parser->parse($children->{$value->name}[$i], $base_obj);

                        if ($value->access_path && $value->type === 'string') {
                            $value->access_path = '(string) '.$value->access_path;
                        }

                        $c->contents[] = $value;

                        ++$i;
                    }
                }
            }

            $o->size = count($c->contents);

            if (!$o->size) {
                $o->size = null;

                if (strlen((string) $var)) {
                    $base_obj = new Kint_Object_Blob();
                    $base_obj->depth = $o->depth + 1;
                    $base_obj->name = $o->name;
                    if ($o->access_path) {
                        $base_obj->access_path = '(string) '.$o->access_path;
                    }

                    $value = (string) $var;

                    $depth_stash = $this->parser->max_depth;
                    $this->parser->max_depth = 0;
                    $value = $this->parser->parse($value, $base_obj);
                    $this->parser->max_depth = $depth_stash;

                    $c = new Kint_Object_Representation('Contents');
                    $c->implicit_label = true;
                    $c->contents = array($value);
                }
            }

            $o->addRepresentation($c, 0);
        }
    }
}
