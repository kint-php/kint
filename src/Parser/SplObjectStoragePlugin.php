<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use SplObjectStorage;

class SplObjectStoragePlugin extends Plugin
{
    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_COMPLETE;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        if (!$var instanceof SplObjectStorage || !($r = $o->getRepresentation('iterator'))) {
            return;
        }

        $r = $o->getRepresentation('iterator');
        if ($r) {
            $o->size = !is_array($r->contents) ? null : count($r->contents);
        }
    }
}
