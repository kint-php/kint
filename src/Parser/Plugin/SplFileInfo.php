<?php

class Kint_Parser_Plugin_SplFileInfo extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!($var instanceof SplFileInfo)) {
            return;
        }

        $r = new Kint_Object_Representation_SplFileInfo(clone $var);
        $o->addRepresentation($r, 0);
        $o->size = $r->getSize();
    }
}
