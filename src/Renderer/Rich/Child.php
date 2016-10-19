<?php

abstract class Kint_Renderer_Rich_Child
{
    protected $renderer;

    public function __construct(Kint_Renderer_Rich $r)
    {
        $this->renderer = $r;
    }

    abstract public function render(Kint_Object_Representation $rep);
}
