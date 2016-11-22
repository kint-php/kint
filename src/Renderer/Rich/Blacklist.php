<?php

class Kint_Renderer_Rich_Blacklist extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        return '<dl>'.self::renderLockedHeader($o, '<var>Blacklisted</var>').'</dl>';
    }
}
