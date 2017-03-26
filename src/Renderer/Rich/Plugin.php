<?php

abstract class Kint_Renderer_Rich_Plugin
{
    protected $renderer;

    public function __construct(Kint_Renderer_Rich $r)
    {
        $this->renderer = $r;
    }

    public static function renderLockedHeader(Kint_Object $o, $content)
    {
        $header = '<dt class="kint-parent kint-locked">';

        if (Kint_Renderer_Rich::$access_paths && $o->depth > 0 && $ap = $o->getAccessPath()) {
            $header .= '<span class="kint-access-path-trigger" title="Show access path">&rlarr;</span>';
        }

        $header .= '<span class="kint-popup-trigger" title="Open in new window">&rarr;</span><nav></nav>';

        if (($s = $o->getModifiers()) !== null) {
            $header .= '<var>'.$s.'</var> ';
        }

        if (($s = $o->getName()) !== null) {
            $header .= '<dfn>'.Kint_Object_Blob::escape($s).'</dfn> ';

            if ($s = $o->getOperator()) {
                $header .= Kint_Object_Blob::escape($s, 'ASCII').' ';
            }
        }

        if (($s = $o->getType()) !== null) {
            $s = Kint_Object_Blob::escape($s);

            if ($o->reference) {
                $s = '&amp;'.$s;
            }

            $header .= '<var>'.$s.'</var> ';
        }

        if (($s = $o->getSize()) !== null) {
            $header .= '('.Kint_Object_Blob::escape($s).') ';
        }

        $header .= $content;

        if (!empty($ap)) {
            $header .= '<div class="access-path">'.Kint_Object_Blob::escape($ap).'</div>';
        }

        return $header.'</dt>';
    }

    abstract public function render($o);
}
