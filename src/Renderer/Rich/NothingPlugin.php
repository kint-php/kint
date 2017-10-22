<?php

namespace Kint\Renderer\Rich;

use Kint\Object\BasicObject;

class NothingPlugin extends Plugin implements ObjectPluginInterface
{
    public function renderObject(BasicObject $o)
    {
        return '<dl><dt><var>No argument</var></dt></dl>';
    }
}
