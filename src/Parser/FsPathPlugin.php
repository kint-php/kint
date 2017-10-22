<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\Representation\SplFileInfoRepresentation;
use SplFileInfo;

class FsPathPlugin extends Plugin
{
    public static $blacklist = array('/', '.');

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
        if (strlen($var) > 2048 || preg_match('/[:?<>"*|]/', $var) || !preg_match('/[\\/\\.\\'.DIRECTORY_SEPARATOR.']/', $var) || !@file_exists($var) || in_array($var, self::$blacklist)) {
            return;
        }

        $r = new SplFileInfoRepresentation(new SplFileInfo($var));
        $r->hints[] = 'fspath';
        $o->addRepresentation($r, 0);
    }
}
