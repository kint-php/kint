<?php

namespace Kint\Renderer\Rich;

use Kint\Renderer\RichRenderer;

interface PluginInterface
{
    public function __construct(RichRenderer $r);
}
