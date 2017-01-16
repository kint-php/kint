<?php

class Kint_Parser_FsPath extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!is_string($var) || strlen($var) > 2048 || preg_match('/[:?<>"*|]/', $var) || !preg_match('/[\\/\\.\\'.DIRECTORY_SEPARATOR.']/', $var) || !@file_exists($var)) {
            return;
        }

        $r = new Kint_Object_Representation_SplFileInfo(new SplFileInfo($var));
        $r->hints[] = 'fspath';
        $o->addRepresentation($r, 0);
    }
}
