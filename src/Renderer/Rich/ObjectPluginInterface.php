<?php

namespace Kint\Renderer\Rich;

use Kint\Object\BasicObject;

interface ObjectPluginInterface extends PluginInterface
{
    public function renderObject(BasicObject $o);
}
