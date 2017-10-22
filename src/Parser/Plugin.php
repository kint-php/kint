<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;

abstract class Plugin
{
    protected $parser;

    public function setParser(Parser $p)
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
        return Parser::TRIGGER_NONE;
    }

    abstract public function parse(&$variable, BasicObject &$o, $trigger);
}
