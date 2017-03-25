<?php

abstract class Kint_Parser_Plugin
{
    protected $parser;

    public function setParser(Kint_Parser $p)
    {
        $this->parser = $p;
    }

    /**
     * An array of types (As returned by gettype) for all data this plugin can operate on.
     *
     * @return array List of types
     */
    public function getTypes()
    {
        return array();
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_NONE;
    }

    abstract public function parse(&$variable, Kint_Object &$o, $trigger);
}
