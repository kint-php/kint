<?php

namespace Kint\Parser;

abstract class Plugin
{
    protected $parser;

    public function __construct(\Kint\Parser $p)
    {
        $this->parser = $p;
    }

    abstract public function parse(&$variable, \Kint\Object &$o);
}
