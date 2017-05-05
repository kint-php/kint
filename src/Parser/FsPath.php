<?php

class Kint_Parser_FsPath extends Kint_Parser_Plugin
{
    public static $blacklist = array('/', '.');

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
        if (strlen($var) > 2048 || preg_match('/[:?<>"*|]/', $var) || !preg_match('/[\\/\\.\\'.DIRECTORY_SEPARATOR.']/', $var) || !@file_exists($var) || in_array($var, self::$blacklist)) {
            return;
        }

        $r = new Kint_Object_Representation_SplFileInfo(new SplFileInfo($var));
        $r->hints[] = 'fspath';
        $o->addRepresentation($r, 0);
    }
}
