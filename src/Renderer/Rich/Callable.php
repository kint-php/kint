<?php

class Kint_Renderer_Rich_Callable extends Kint_Renderer_Rich_Header
{
    public function render(Kint_Object $o)
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
            if (Kint_Object_Blob::strlen($s) > Kint::$max_str_length) {
                $s = substr($s, 0, Kint::$max_str_length).'...';
            }
            $header .= ' '.Kint_Object_Blob::escape($s);
        }

        // $header .= Kint_Object_Blob::escape(Kint::shortenPath($o->filename)).':'.(int) $o->startline;
        $header = Kint_Renderer_Rich::renderHeaderWrapper($o, (bool) strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }
}
