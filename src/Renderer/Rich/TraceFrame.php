<?php

class Kint_Renderer_Rich_TraceFrame extends Kint_Renderer_Rich_Plugin
{
    public function render($o)
    {
        $children = $this->renderer->renderChildren($o);

        if (!($o instanceof Kint_Object_TraceFrame)) {
            $header = $this->renderer->renderHeader($o);
        } else {
            if (!empty($o->trace['file']) && !empty($o->trace['line'])) {
                $header = '<var>'.Kint_Object_Blob::escape(Kint::shortenPath($o->trace['file'])).':'.(int) $o->trace['line'].'</var> ';
            } else {
                $header = '<var>PHP internal call</var> ';
            }

            if ($o->trace['class']) {
                $header .= Kint_Object_Blob::escape($o->trace['class'].$o->trace['type']);
            }

            if (is_string($o->trace['function'])) {
                $function = $o->trace['function'].'()';
            } else {
                $function = $o->trace['function']->getName().'('.$o->trace['function']->getParams().')';
            }

            $header .= '<dfn>'.Kint_Object_Blob::escape($function).'</dfn>';
        }

        $header = $this->renderer->renderHeaderWrapper($o, (bool) strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }
}
