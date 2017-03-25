<?php

class Kint_Parser_DOMIterator extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        // Recursion should never happen, should always be stopped at the parent
        // DOMNode.  Depth limit on the other hand we're going to skip since
        // that would show an empty iterator and rather useless. Let the depth
        // limit hit the children (DOMNodeList only has DOMNode as children)
        return Kint_Parser::TRIGGER_COMPLETE & ~Kint_Parser::TRIGGER_RECURSION;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (!($var instanceof DOMNamedNodeMap || $var instanceof DOMNodeList)) {
            return;
        }

        $o->size = $var->length;
        if ($o->size === 0) {
            $o->replaceRepresentation(new Kint_Object_Representation('Iterator'));
            $o->size = null;

            return;
        }

        // Depth limit
        // Make empty iterator representation since we need it in DOMNode to point out depth limits
        if ($this->parser->max_depth && $o->depth + 1 >= $this->parser->max_depth) {
            $b = new Kint_Object();
            $b->name = $o->classname.' Iterator Contents';
            $b->access_path = 'iterator_to_array('.$o->access_path.')';
            $b->depth = $o->depth + 1;
            $b->hints[] = 'depth_limit';

            $r = new Kint_Object_Representation('Iterator');
            $r->contents = array($b);
            $o->replaceRepresentation($r, 0);

            return;
        }

        // In 5.1 you can interate them but they're not traversable.
        // DomDoc. The gift that keeps on giving.
        if (!$var instanceof Traversable) {
            $data = array();
            foreach ($var as $item) {
                $data[] = $item;
            }
        } else {
            $data = iterator_to_array($var);
        }

        $r = new Kint_Object_Representation('Iterator');
        $o->replaceRepresentation($r, 0);

        foreach ($data as $key => $item) {
            $base_obj = new Kint_Object();
            $base_obj->depth = $o->depth + 1;
            $base_obj->name = $item->nodeName;

            if ($o->access_path) {
                if ($var instanceof DOMNamedNodeMap) {
                    $base_obj->access_path = $o->access_path.'->getNamedItem('.var_export($key, true).')';
                } elseif ($var instanceof DOMNodeList) {
                    $base_obj->access_path = $o->access_path.'->item('.var_export($key, true).')';
                } else {
                    $base_obj->access_path = 'iterator_to_array('.$o->access_path.')';
                }
            }

            $r->contents[] = $this->parser->parse($item, $base_obj);
        }
    }
}
