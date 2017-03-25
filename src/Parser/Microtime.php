<?php

class Kint_Parser_Microtime extends Kint_Parser_Plugin
{
    private static $last = null;
    private static $start = null;
    private static $times = 0;
    private static $group = 0;

    public function getTypes()
    {
        return array('string');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (!preg_match('/0\.[0-9]{8} [0-9]{10}/', $var)) {
            return;
        }

        if ($o->name !== 'microtime()' || $o->depth !== 0) {
            return;
        }

        list($usec, $sec) = explode(' ', $var);

        $time = (float) $usec + (float) $sec;

        if (self::$last !== null) {
            $last_time = array_sum(array_map('floatval', explode(' ', self::$last)));
            $lap = $time - $last_time;
            ++self::$times;
        } else {
            $lap = null;
            self::$start = $time;
        }

        self::$last = $var;

        if ($lap !== null) {
            $total = $time - self::$start;
            $r = new Kint_Object_Representation_Microtime(self::$group, $lap, $total, self::$times);
        } else {
            $r = new Kint_Object_Representation_Microtime(self::$group);
        }
        $r->contents = $var;
        $r->implicit_label = true;

        $o->removeRepresentation($o->value->name);
        $o->addRepresentation($r);
    }

    public static function clean()
    {
        self::$last = null;
        self::$start = null;
        self::$times = 0;
        ++self::$group;
    }
}
