<?php

abstract class Kint_Parser_Plugin
{
    protected $parser;

    public function __construct(Kint_Parser $p)
    {
        $this->parser = $p;
    }

    abstract public function parse(&$variable, Kint_Object &$o);
}
