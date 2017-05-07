<?php

class Kint_Renderer_Rich_Recursion extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        return '<dl>'.$this->renderHeaderLocked($o, '<var>Recursion</var>').'</dl>';
    }
}
