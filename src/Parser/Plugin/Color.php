<?php

class Kint_Parser_Plugin_Color extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!is_string($var)) {
            return;
        }

        $rep = new Kint_Object_Representation_Color($var);

        if ($rep->variant) {
            $o = $o->transplant(new Kint_Object_Color($rep));
            $o->removeRepresentation($o->value->name);
            $o->addRepresentation($rep, 0);
        }
    }
}
