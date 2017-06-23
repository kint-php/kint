<?php

class Kint_Parser_Color extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        return array('string');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (strlen($var) > 32) {
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
