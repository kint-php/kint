<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\BlobObject;

class BinaryPlugin extends Plugin
{
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
        if (!$o instanceof BlobObject || !in_array($o->encoding, array('ASCII', 'UTF-8'))) {
            $o->value->hints[] = 'binary';
        }
    }
}
