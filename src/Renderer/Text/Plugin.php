<?php

abstract class Kint_Renderer_Text_Plugin
{
    protected $renderer;

    public function __construct(Kint_Renderer_Text $r)
    {
        $this->renderer = $r;
    }

    abstract public function render($o);
}
