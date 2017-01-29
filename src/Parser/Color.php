<?php

class Kint_Parser_Color extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!is_string($var) || strlen($var) > 100) {
            return;
        }

        $trimmed = strtolower(trim($var));

        if (!isset(Kint_Object_Color::$color_map[$trimmed]) && !preg_match('/^(?:(?:rgb|hsl)[^\)]{6,}\)|#[0-9a-fA-F]{3,8})$/', $trimmed)) {
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
