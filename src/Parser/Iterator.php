<?php

class Kint_Parser_Iterator extends Kint_Parser_Plugin
{
    /**
     * List of classes and interfaces to blacklist.
     *
     * Certain classes (Such as PDOStatement) irreversibly lose information
     * when traversed. Others are just huge. Either way, put them in here
     * and you won't have to worry about them being parsed.
     *
     * @var array
     */
    public static $blacklist = array(
        'PDOStatement',
        'DOMNodeList',
        'DOMNamedNodeMap',
    );

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
        if (!$var instanceof Traversable) {
            return;
        }

        foreach (self::$blacklist as $class) {
            if ($var instanceof $class) {
                $b = new Kint_Object();
                $b->name = $class.' Iterator Contents';
                $b->access_path = 'iterator_to_array('.$o->access_path.', true)';
                $b->depth = $o->depth + 1;
                $b->hints[] = 'blacklist';

                $r = new Kint_Object_Representation('Iterator');
                $r->contents = array($b);

                $o->addRepresentation($r);

                return;
            }
        }

        $data = iterator_to_array($var);

        if ($data === false) {
            return;
        }

        $base_obj = new Kint_Object();
        $base_obj->depth = $o->depth;

        if ($o->access_path) {
            $base_obj->access_path = 'iterator_to_array('.$o->access_path.')';
        }

        $r = new Kint_Object_Representation('Iterator');
        $r->contents = $this->parser->parse($data, $base_obj);
        $r->contents = $r->contents->value->contents;

        $primary = reset($o->representations);
        if ($primary && $primary === $o->value && $primary->contents === array()) {
            $o->addRepresentation($r, 0);
        } else {
            $o->addRepresentation($r);
        }
    }
}
