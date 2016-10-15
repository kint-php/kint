<?php

class Kint_Parser_Plugin_FsPath extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!is_string($var) || strlen($var) > 2048 || preg_match('[[:?<>"*|]]', $var) || !@is_readable($var)) {
            return;
        }

        if (class_exists('SplFileInfo')) {
            $r = new Kint_Object_Representation_FsPath(new SplFileInfo($var));
            $o->addRepresentation($r, 0);
        }
    }
}
