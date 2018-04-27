<?php

namespace Kint\Renderer\Text;

use Kint\Object\BasicObject;
use Kint\Object\Representation\MicrotimeRepresentation;
use Kint\Renderer\PlainRenderer;
use Kint\Renderer\TextRenderer;
use Kint\Renderer\Rich\MicrotimePlugin as RichPlugin;
use Kint\Utils;

class MicrotimePlugin extends Plugin
{
    protected $useJs = false;

    public function __construct(TextRenderer $r)
    {
        parent::__construct($r);

        if ($this->renderer instanceof PlainRenderer) {
            $this->useJs = true;
        }
    }

    public function render(BasicObject $o)
    {
        $r = $o->getRepresentation('microtime');

        if (!$r instanceof MicrotimeRepresentation) {
            return false;
        }

        $out = '';

        if ($o->depth == 0) {
            $out .= $this->renderer->colorTitle($this->renderer->renderTitle($o)).PHP_EOL;
        }

        $out .= $this->renderer->renderHeader($o);
        $out .= $this->renderer->renderChildren($o).PHP_EOL;

        $indent = str_repeat(' ', ($o->depth + 1) * $this->renderer->indent_width);

        if ($this->useJs) {
            $out .= '<span data-kint-microtime-group="'.$r->group.'">';
        }

        $out .= $indent.$this->renderer->colorType('TIME:').' ';
        $out .= $this->renderer->colorValue($r->getDateTime()->format('Y-m-d H:i:s.u')).PHP_EOL;

        if ($r->lap !== null) {
            $out .= $indent.$this->renderer->colorType('SINCE LAST CALL:').' ';

            $lap = round($r->lap, 4);

            if ($this->useJs) {
                $lap = '<span class="kint-microtime-lap">'.$lap.'</span>';
            }

            $out .= $this->renderer->colorValue($lap.'s').'.'.PHP_EOL;
        }
        if ($r->total !== null) {
            $out .= $indent.$this->renderer->colorType('SINCE START:').' ';
            $out .= $this->renderer->colorValue(round($r->total, 4).'s').'.'.PHP_EOL;
        }
        if ($r->avg !== null) {
            $out .= $indent.$this->renderer->colorType('AVERAGE DURATION:').' ';

            $avg = round($r->avg, 4);

            if ($this->useJs) {
                $avg = '<span class="kint-microtime-avg">'.$avg.'</span>';
            }

            $out .= $this->renderer->colorValue($avg.'s').'.'.PHP_EOL;
        }

        $bytes = Utils::getHumanReadableBytes($r->mem);
        $mem = $r->mem.' bytes ('.round($bytes['value'], 3).' '.$bytes['unit'].')';
        $bytes = Utils::getHumanReadableBytes($r->mem_real);
        $mem .= ' (real '.round($bytes['value'], 3).' '.$bytes['unit'].')';

        $out .= $indent.$this->renderer->colorType('MEMORY USAGE:').' ';
        $out .= $this->renderer->colorValue($mem).'.'.PHP_EOL;

        $bytes = Utils::getHumanReadableBytes($r->mem_peak);
        $mem = $r->mem_peak.' bytes ('.round($bytes['value'], 3).' '.$bytes['unit'].')';
        $bytes = Utils::getHumanReadableBytes($r->mem_peak_real);
        $mem .= ' (real '.round($bytes['value'], 3).' '.$bytes['unit'].')';

        $out .= $indent.$this->renderer->colorType('PEAK MEMORY USAGE:').' ';
        $out .= $this->renderer->colorValue($mem).'.'.PHP_EOL;

        if ($this->useJs) {
            $out .= '</span>';
        }

        return $out;
    }

    public static function renderJs()
    {
        return RichPlugin::renderJs();
    }
}
