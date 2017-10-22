<?php

namespace Kint\Renderer\Text;

use Kint\Object\BasicObject;

class BlacklistPlugin extends Plugin
{
    public function render(BasicObject $o)
    {
        $out = '';

        if ($o->depth == 0) {
            $out .= $this->renderer->colorTitle($this->renderer->renderTitle($o)).PHP_EOL;
        }

        $out .= $this->renderer->renderHeader($o).' '.$this->renderer->colorValue('BLACKLISTED').PHP_EOL;

        return $out;
    }
}
