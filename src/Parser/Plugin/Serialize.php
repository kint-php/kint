<?php

class Kint_Parser_Plugin_Serialize extends Kint_Parser_Plugin
{
    public static $options = array(true);

    public function parse(&$var, Kint_Object &$o)
    {
        if (!is_string($var)) {
            return;
        }

        $trimmed = rtrim($var);

        if ($trimmed !== 'N;' && !preg_match('/^[COabdis]:\d+[:;]/', $trimmed)) {
            return;
        }

        // Second parameter only supported on PHP 7
        if (KINT_PHP70) {
            // Suppress warnings on unserializeable variable
            $data = @unserialize($trimmed, self::$options);
        } else {
            $data = @unserialize($trimmed);
        }

        if ($data === false && $trimmed !== 'b:0;') {
            return;
        }

        $base_obj = new Kint_Object();
        $base_obj->depth = $o->depth + 1;
        $base_obj->name = 'unserialize('.$o->name.')';

        if ($o->access_path) {
            $base_obj->access_path = 'unserialize('.$o->access_path;
            if (!KINT_PHP70 || self::$options === array(true)) {
                $base_obj->access_path .= ')';
            } elseif (self::$options === array(false)) {
                $base_obj->access_path .= ', false)';
            } else {
                $base_obj->access_path .= ', Kint_Parser_Plugin_Serialize::$options)';
            }
        }

        $r = new Kint_Object_Representation('Serialized');
        $r->contents = $this->parser->parse($data, $base_obj);

        if (!in_array('depth_limit', $r->contents->hints) && is_array($data)) {
            $r->contents = $r->contents->value_representation->contents;
        }

        $o->addRepresentation($r, 0);
    }
}
