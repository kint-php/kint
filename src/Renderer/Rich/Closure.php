<?php

class Kint_Renderer_Rich_Closure extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        $children = $this->renderer->renderChildren($o);

        if (!($o instanceof Kint_Object_Closure)) {
            $header = Kint_Renderer_Rich::renderHeader($o);
        } else {
            $header = '';

            if (($s = $o->getModifiers()) !== null) {
                $header .= '<var>'.$s.'</var> ';
            }

            if (($s = $o->getName()) !== null) {
                $header .= '<dfn>'.Kint_Object_Blob::escape($s.'('.$o->getParams().')').'</dfn> ';
            }

            $header .= '<var>Closure</var>';

            $header .= Kint_Object_Blob::escape(Kint::shortenPath($o->filename)).':'.(int) $o->startline;
        }

        $header = Kint_Renderer_Rich::renderHeaderWrapper($o, (bool) strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }
}
