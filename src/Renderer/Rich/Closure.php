<?php

class Kint_Renderer_Rich_Closure extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        $children = $this->renderer->renderChildren($o);

        if (!($o instanceof Kint_Object_Closure)) {
            $header = $this->renderer->renderHeader($o);
        } else {
            $header = '';

            if (($s = $o->getModifiers()) !== null) {
                $header .= '<var>'.$s.'</var> ';
            }

            if (($s = $o->getName()) !== null) {
                $header .= '<dfn>'.$this->renderer->escape($s).'('.$this->renderer->escape($o->getParams()).')</dfn> ';
            }

            $header .= '<var>Closure</var> ';
            $header .= $this->renderer->escape(Kint::shortenPath($o->filename)).':'.(int) $o->startline;
        }

        $header = $this->renderer->renderHeaderWrapper($o, (bool) strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }
}
