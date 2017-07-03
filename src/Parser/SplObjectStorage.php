<?php

class Kint_Parser_SplObjectStorage extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        if (KINT_PHP53) {
            return array('object');
        } else {
            return array();
        }
    }

    public function getTriggers()
    {
        if (KINT_PHP53) {
            return Kint_Parser::TRIGGER_COMPLETE;
        } else {
            return Kint_Parser::TRIGGER_NONE;
        }
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
