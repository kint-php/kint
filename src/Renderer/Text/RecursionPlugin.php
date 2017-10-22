<?php

namespace Kint\Renderer\Text;

use Kint\Object\BasicObject;

class RecursionPlugin extends Plugin
{
    public function render(BasicObject $o)
    {
        $out = '';

        if ($o->depth == 0) {
            $out .= $this->renderer->colorTitle($this->renderer->renderTitle($o)).PHP_EOL;
        }

        $out .= $this->renderer->renderHeader($o).' '.$this->renderer->colorValue('RECURSION').PHP_EOL;

        return $out;
    }
}
