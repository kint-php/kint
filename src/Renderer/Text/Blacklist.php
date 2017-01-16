<?php

class Kint_Renderer_Text_Blacklist extends Kint_Renderer_Text_Plugin
{
    public function render($o)
    {
        $out = '';

        if ($o->depth == 0) {
            $out .= $this->renderer->colorTitle($this->renderer->renderTitle($o)).PHP_EOL;
        }

        $out .= $this->renderer->renderHeader($o).' '.$this->renderer->colorValue('BLACKLISTED').PHP_EOL;

        return $out;
    }
}
