<?php

class Kint_Renderer_Rich_ColorDetails extends Kint_Renderer_Rich_Plugin
{
    public function render($r)
    {
        if (!$r instanceof Kint_Object_Representation_Color) {
            return false;
        }

        $out = '';

        if ($color = $r->getColor(Kint_Object_Representation_Color::COLOR_NAME)) {
            $out .= '<dfn>'.$color."</dfn>\n";
        }
        if ($color = $r->getColor(Kint_Object_Representation_Color::COLOR_HEX_3)) {
            $out .= '<dfn>'.$color."</dfn>\n";
        }
        if ($color = $r->getColor(Kint_Object_Representation_Color::COLOR_HEX_6)) {
            $out .= '<dfn>'.$color."</dfn>\n";
        }

        if ($r->hasAlpha()) {
            if ($color = $r->getColor(Kint_Object_Representation_Color::COLOR_HEX_4)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
            if ($color = $r->getColor(Kint_Object_Representation_Color::COLOR_HEX_8)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
            if ($color = $r->getColor(Kint_Object_Representation_Color::COLOR_RGBA)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
            if ($color = $r->getColor(Kint_Object_Representation_Color::COLOR_HSLA)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
        } else {
            if ($color = $r->getColor(Kint_Object_Representation_Color::COLOR_RGB)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
            if ($color = $r->getColor(Kint_Object_Representation_Color::COLOR_HSL)) {
                $out .= '<dfn>'.$color."</dfn>\n";
            }
        }

        if (!strlen($out)) {
            return false;
        }

        return '<pre>'.$out.'</pre>';
    }
}
