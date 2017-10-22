<?php

namespace Kint\Renderer\Rich;

use Kint\Object\BasicObject;

class DepthLimitPlugin extends Plugin implements ObjectPluginInterface
{
    public function renderObject(BasicObject $o)
    {
        return '<dl>'.$this->renderLockedHeader($o, '<var>Depth Limit</var>').'</dl>';
    }
}
