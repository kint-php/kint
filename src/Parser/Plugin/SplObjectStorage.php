<?php

class Kint_Parser_Plugin_SplObjectStorage extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!($var instanceof SplObjectStorage) || !($r = $o->getRepresentation('iterator'))) {
            return;
        }

        $r = $o->getRepresentation('iterator');
        if ($r) {
            $o->size = !is_array($r->contents) ? null : count($r->contents);
        }
    }
}
