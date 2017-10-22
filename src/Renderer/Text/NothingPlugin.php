<?php

namespace Kint\Renderer\Text;

use Kint\Object\BasicObject;
use Kint\Renderer\TextRenderer;

class NothingPlugin extends Plugin
{
    public function render(BasicObject $o)
    {
        if (TextRenderer::$decorations) {
            return $this->renderer->colorTitle($this->renderer->boxText('No argument', $this->renderer->header_width)).PHP_EOL;
        } else {
            return $this->renderer->colorTitle('No argument').PHP_EOL;
        }
    }
}
