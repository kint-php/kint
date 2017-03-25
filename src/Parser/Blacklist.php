<?php

class Kint_Parser_Blacklist extends Kint_Parser_Plugin
{
    /**
     * List of classes and interfaces to blacklist.
     *
     * @var array
     */
    public static $blacklist = array();

    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_BEGIN;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        foreach (self::$blacklist as $class) {
            if ($var instanceof $class) {
                if (KINT_PHP53 || function_exists('spl_object_hash')) {
                    $hash = spl_object_hash($var);
                } else {
                    ob_start();
                    var_dump($var);
                    preg_match('/#(\d+)/', ob_get_clean(), $match);
                    $hash = $match[1];
                }

                $object = $o->transplant(new Kint_Object_Instance());
                $object->classname = get_class($var);
                $object->hash = $hash;
                $object->clearRepresentations();
                $object->value = null;
                $object->size = null;
                $object->hints[] = 'blacklist';

                $o = $object;

                $this->parser->haltParse();

                return;
            }
        }
    }
}
