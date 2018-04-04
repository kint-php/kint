<?php

namespace Kint\Renderer\Rich;

use Kint\Object\Representation\MicrotimeRepresentation;
use Kint\Object\Representation\Representation;
use Kint\Utils;

class MicrotimePlugin extends Plugin implements TabPluginInterface
{
    public function renderTab(Representation $r)
    {
        if (!($r instanceof MicrotimeRepresentation)) {
            return false;
        }

        $out = $r->getDateTime()->format('Y-m-d H:i:s.u');
        if ($r->lap !== null) {
            $out .= "\n<b>SINCE LAST CALL:</b> <b class=\"kint-microtime-lap\">".round($r->lap, 4).'</b>s.';
        }
        if ($r->total !== null) {
            $out .= "\n<b>SINCE START:</b> ".round($r->total, 4).'s.';
        }
        if ($r->avg !== null) {
            $out .= "\n<b>AVERAGE DURATION:</b> <span class=\"kint-microtime-avg\">".round($r->avg, 4).'</span>s.';
        }

        $bytes = Utils::getHumanReadableBytes($r->mem);
        $out .= "\n<b>MEMORY USAGE:</b> ".$r->mem.' bytes ('.round($bytes['value'], 3).' '.$bytes['unit'].')';
        $bytes = Utils::getHumanReadableBytes($r->mem_real);
        $out .= ' (real '.round($bytes['value'], 3).' '.$bytes['unit'].')';

        $bytes = Utils::getHumanReadableBytes($r->mem_peak);
        $out .= "\n<b>PEAK MEMORY USAGE:</b> ".$r->mem_peak.' bytes ('.round($bytes['value'], 3).' '.$bytes['unit'].')';
        $bytes = Utils::getHumanReadableBytes($r->mem_peak_real);
        $out .= ' (real '.round($bytes['value'], 3).' '.$bytes['unit'].')';

        return '<pre data-kint-microtime-group="'.$r->group.'">'.$out.'</pre>';
    }

    public static function renderJs()
    {
        return file_get_contents(KINT_DIR.'/resources/compiled/rich_microtime.js');
    }
}
