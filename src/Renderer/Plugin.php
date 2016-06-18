<?php

abstract class Kint_Renderer_Plugin
{
    protected $renderer;

    public function __construct(Kint_Renderer $r)
    {
        $this->renderer = $r;
    }

    abstract public function render(Kint_Object_Representation $o);
}
