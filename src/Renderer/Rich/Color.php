<?php

class Kint_Renderer_Rich_Color extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        $children = $this->renderer->renderChildren($o);

        $header = $this->renderer->renderHeader($o);
        $header .= '<div class="kint-color-preview"><div style="background:';
        $header .= $o->color->getColor(Kint_Object_Representation_Color::COLOR_RGBA);
        $header .= '"></div></div>';

        $header = $this->renderer->renderHeaderWrapper($o, (bool) strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }
}
