<?php

namespace Kint\Renderer\Rich;

use Kint\Object\Representation\Representation;

interface TabPluginInterface extends PluginInterface
{
    public function renderTab(Representation $o);
}
