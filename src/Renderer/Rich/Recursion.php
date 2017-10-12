<?php

class Kint_Renderer_Rich_Recursion extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        return '<dl>'.$this->renderLockedHeader($o, '<var>Recursion</var>').'</dl>';
    }
}
