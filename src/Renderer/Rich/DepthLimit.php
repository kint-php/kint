<?php

class Kint_Renderer_Rich_DepthLimit extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        $header = Kint_Renderer_Rich::renderHeader($o);
        $header = Kint_Renderer_Rich::renderHeaderWrapper($o, true, $header);
        return '<dl>'.$header.'<dd><dl><dt><var>Depth limit</var></dt></dl></dd></dl>';
    }
}
