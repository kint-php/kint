<?php

class Kint_Renderer_Rich_Callable extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        $children = $this->renderer->renderChildren($o);

        $header = '';

        if (($s = $o->getModifiers()) !== null) {
            $header .= '<var>'.$s.'</var> ';
        }

        if (($s = $o->getName()) !== null) {
            $header .= '<dfn>'.Kint_Object_Blob::escape($s.'('.$o->getParams().')').'</dfn>';
        }

        if (!empty($o->returntype)) {
            $header .= ': <var>'.Kint_Object_Blob::escape($o->returntype).'</var>';
        }

        if (($s = $o->getValueShort()) !== null) {
            if (Kint_Renderer_Rich::$strlen_max && Kint_Object_Blob::strlen($s) > Kint_Renderer_Rich::$strlen_max) {
                $s = substr($s, 0, Kint_Renderer_Rich::$strlen_max).'...';
            }
            $header .= ' '.Kint_Object_Blob::escape($s);
        }

        $header = Kint_Renderer_Rich::renderHeaderWrapper($o, (bool) strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }
}
