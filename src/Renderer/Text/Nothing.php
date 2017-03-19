<?php

class Kint_Renderer_Text_Nothing extends Kint_Renderer_Text_Plugin
{
    public function render($o)
    {
        if (Kint_Renderer_Text::$decorations) {
            return $this->renderer->colorTitle($this->renderer->boxText('No argument', $this->renderer->header_width)).PHP_EOL;
        } else {
            return $this->renderer->colorTitle('No argument').PHP_EOL;
        }
    }
}
