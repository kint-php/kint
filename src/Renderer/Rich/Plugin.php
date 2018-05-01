<?php

namespace Kint\Renderer\Rich;

use Kint\Object\BasicObject;
use Kint\Renderer\RichRenderer;

abstract class Plugin implements PluginInterface
{
    protected $renderer;

    public function __construct(RichRenderer $r)
    {
        $this->renderer = $r;
    }

    /**
     * Renders a locked header.
     */
    public function renderLockedHeader(BasicObject $o, $content)
    {
        $header = '<dt class="kint-parent kint-locked">';

        if (RichRenderer::$access_paths && $o->depth > 0 && $ap = $o->getAccessPath()) {
            $header .= '<span class="kint-access-path-trigger" title="Show access path">&rlarr;</span>';
        }

        $header .= '<span class="kint-popup-trigger" title="Open in new window">&boxbox;</span><nav></nav>';

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
}
