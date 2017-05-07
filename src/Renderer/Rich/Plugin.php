<?php

abstract class Kint_Renderer_Rich_Plugin
{
    protected $renderer;

    public function __construct(Kint_Renderer_Rich $r)
    {
        $this->renderer = $r;
    }

    /**
     * Renders a locked header.
     *
     * This is a placeholder until Kint 3 when it can be renamed to
     * renderLockedHeader after the deprecated static method is removed
     */
    public function renderHeaderLocked(Kint_Object $o, $content)
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
            $header .= '<dfn>'.$this->renderer->escape($s).'</dfn> ';

            if ($s = $o->getOperator()) {
                $header .= $this->renderer->escape($s, 'ASCII').' ';
            }
        }

        if (($s = $o->getType()) !== null) {
            $s = $this->renderer->escape($s);

            if ($o->reference) {
                $s = '&amp;'.$s;
            }

            $header .= '<var>'.$s.'</var> ';
        }

        if (($s = $o->getSize()) !== null) {
            $header .= '('.$this->renderer->escape($s).') ';
        }

        $header .= $content;

        if (!empty($ap)) {
            $header .= '<div class="access-path">'.$this->renderer->escape($ap).'</div>';
        }

        return $header.'</dt>';
    }

    public static function renderLockedHeader(Kint_Object $o, $content)
    {
        static $show_dep = true;

        if ($show_dep) {
            trigger_error('Kint_Renderer_Rich_Plugin::renderLockedHeader() is deprecated and will be removed in Kint 3.0. Use renderHeaderLocked instead.', KINT_PHP53 ? E_USER_DEPRECATED : E_USER_NOTICE);
            $show_dep = false;
        }

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
