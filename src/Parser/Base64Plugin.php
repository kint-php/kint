<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\Representation\Representation;

class Base64Plugin extends Plugin
{
    /**
     * The minimum length before a string will be considered for base64 decoding.
     *
     * @var int
     */
    public static $min_length_hard = 16;

    /**
     * The minimum length before the base64 decoding will take precedence.
     *
     * @var int
     */
    public static $min_length_soft = 50;

    public function getTypes()
    {
        return array('string');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        if (strlen($var) < self::$min_length_hard || !preg_match('%^(?:[A-Za-z0-9+/=]{4})+$%', $var)) {
            return;
        }

        $data = base64_decode($var);

        if ($data === false) {
            return;
        }

        $base_obj = new BasicObject();
        $base_obj->depth = $o->depth + 1;
        $base_obj->name = 'base64_decode('.$o->name.')';

        if ($o->access_path) {
            $base_obj->access_path = 'base64_decode('.$o->access_path.')';
        }

        $r = new Representation('Base64');
        $r->contents = $this->parser->parse($data, $base_obj);

        if (strlen($var) > self::$min_length_soft) {
            $o->addRepresentation($r, 0);
        } else {
            $o->addRepresentation($r);
        }
    }
}
