<?php

class Kint_Renderer_Rich_Nothing extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        return '<dl><dt><var>No argument</var></dt></dl>';
    }
}
