<?php

class Kint_Parser_SplObjectStorage extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_COMPLETE;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
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
