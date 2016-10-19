<?php

class Kint_Renderer_Rich_DepthLimit extends Kint_Renderer_Rich_Header
{
    public function render(Kint_Object $o)
    {
        $header = Kint_Renderer_Rich::renderHeader($o, true);
        $header = Kint_Renderer_Rich::renderHeaderWrapper($o, true, $header);
        return '<dl>'.$header.'<dd><dl><dt><var>Depth limit</var></td></dl></dd></dl>';
    }
}
