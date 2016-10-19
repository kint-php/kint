<?php

class Kint_Renderer_Rich_Nothing extends Kint_Renderer_Rich_Header
{
    public function render(Kint_Object $o)
    {
        return '<dl><dt><var>No argument</var></td></dl>';
    }
}
