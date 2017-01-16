<?php

class Kint_Parser_Blacklist extends Kint_Parser_Plugin
{
    /**
     * List of classes and interfaces to blacklist.
     *
     * @var array
     */
    public static $blacklist = array(
    );

    public function parse(&$var, Kint_Object &$o)
    {
        if (!is_object($var)) {
            return;
        }

        foreach (self::$blacklist as $class) {
            if ($var instanceof $class) {
                $o->clearRepresentations();
                $o->value = null;
                $o->size = null;
                $o->hints[] = 'blacklist';

                $this->parser->haltPlugins();

                return;
            }
        }
    }
}
