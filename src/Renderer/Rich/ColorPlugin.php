<?php

namespace Kint\Renderer\Rich;

use Kint\Object\BasicObject;
use Kint\Object\ColorObject;
use Kint\Object\Representation\ColorRepresentation;
use Kint\Object\Representation\Representation;

class ColorPlugin extends Plugin implements TabPluginInterface, ObjectPluginInterface
{
    public function renderObject(BasicObject $o)
    {
        if (!$o instanceof ColorObject) {
            return;
        }

        $children = $this->renderer->renderChildren($o);

        $header = $this->renderer->renderHeader($o);
        $header .= '<div class="kint-color-preview"><div style="background:';
        $header .= $o->color->getColor(ColorRepresentation::COLOR_RGBA);
        $header .= '"></div></div>';

        $header = $this->renderer->renderHeaderWrapper($o, (bool) strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }

    public function renderTab(Representation $r)
    {
        if (!$r instanceof ColorRepresentation) {
            return false;
        }

        $out = '';

        if ($color = $r->getColor(ColorRepresentation::COLOR_NAME)) {
            $out .= '<dfn>'.$color."</dfn>\n";
        }
        if ($color = $r->getColor(ColorRepresentation::COLOR_HEX_3)) {
            $out .= '<dfn>'.$color."</dfn>\n";
        }
        if ($color = $r->getColor(ColorRepresentation::COLOR_HEX_6)) {
            $out .= '<dfn>'.$color."</dfn>\n";
        }

        if ($r->hasAlpha()) {
            if ($color = $r->getColor(ColorRepresentation::COLOR_HEX_4)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
            if ($color = $r->getColor(ColorRepresentation::COLOR_HEX_8)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
            if ($color = $r->getColor(ColorRepresentation::COLOR_RGBA)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
            if ($color = $r->getColor(ColorRepresentation::COLOR_HSLA)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
        } else {
            if ($color = $r->getColor(ColorRepresentation::COLOR_RGB)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
            if ($color = $r->getColor(ColorRepresentation::COLOR_HSL)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
        }

        if (!strlen($out)) {
            return false;
        }

        return '<pre>'.$out.'</pre>';
    }
}
