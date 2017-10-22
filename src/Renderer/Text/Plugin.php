<?php

namespace Kint\Renderer\Text;

use Kint\Object\BasicObject;
use Kint\Renderer\TextRenderer;

abstract class Plugin
{
    protected $renderer;

    public function __construct(TextRenderer $r)
    {
        $this->renderer = $r;
    }

    abstract public function render(BasicObject $o);
}
