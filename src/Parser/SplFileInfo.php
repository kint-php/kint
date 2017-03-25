<?php

class Kint_Parser_SplFileInfo extends Kint_Parser_Plugin
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
        if (!$var instanceof SplFileInfo) {
            return;
        }

        $r = new Kint_Object_Representation_SplFileInfo(clone $var);
        $o->addRepresentation($r, 0);
        $o->size = $r->getSize();
    }
}
