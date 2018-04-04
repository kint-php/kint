<?php

namespace Kint\Renderer\Text;

use Kint\Object\BasicObject;
use Kint\Object\Representation\MicrotimeRepresentation;
use Kint\Utils;

class MicrotimePlugin extends Plugin
{
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

        $out .= $indent.$this->renderer->colorType('TIME:').' ';
        $out .= $this->renderer->colorValue($r->getDateTime()->format('Y-m-d H:i:s.u')).PHP_EOL;

        if ($r->lap !== null) {
            $out .= $indent.$this->renderer->colorType('SINCE LAST CALL:').' ';
            $out .= $this->renderer->colorValue(round($r->lap, 4).'s').'.'.PHP_EOL;
        }
        if ($r->total !== null) {
            $out .= $indent.$this->renderer->colorType('SINCE START:').' ';
            $out .= $this->renderer->colorValue(round($r->total, 4).'s').'.'.PHP_EOL;
        }
        if ($r->avg !== null) {
            $out .= $indent.$this->renderer->colorType('AVERAGE DURATION:').' ';
            $out .= $this->renderer->colorValue(round($r->avg, 4).'s').'.'.PHP_EOL;
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

        return $out;
    }
}
