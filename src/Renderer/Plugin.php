<?php

namespace Kint\Renderer;

abstract class Plugin
{
    protected $renderer;

    public function __construct(\Kint\Renderer $r)
    {
        $this->renderer = $r;
    }

    abstract public function render(\Kint\Object\Representation $o);
}
