<?php

class Kint_Renderer_Plugin_MicrotimeRich extends Kint_Renderer_Plugin
{
    public function render(Kint_Object_Representation $r)
    {
        if (!($r instanceof Kint_Object_Representation_Microtime)) {
            return false;
        }

        list($usec, $sec) = explode(' ', $r->contents);

        // '@' is used to prevent the dreaded timezone not set error
        $out = @date('Y-m-d H:i:s', $sec).'.'.substr($usec, 2, 4);
        if ($r->lap !== null) {
            $out .= "\n<b>SINCE LAST CALL:</b> <b class=\"kint-microtime\">".round($r->lap, 4).'</b>s.';
        }
        if ($r->total !== null) {
            $out .= "\n<b>SINCE START:</b> ".round($r->total, 4).'s.';
        }
        if ($r->avg !== null) {
            $out .= "\n<b>AVERAGE DURATION:</b> ".round($r->avg, 4).'s.';
        }

        $unit = array('B', 'KB', 'MB', 'GB', 'TB');

        $out .= "\n<b>MEMORY USAGE:</b> ".$r->mem.' bytes (';
        $i = floor(log($r->mem, 1024));
        $out .= round($r->mem / pow(1024, $i), 3).' '.$unit[$i].')';
        $i = floor(log($r->mem_real, 1024));
        $out .= ' (real '.round($r->mem_real / pow(1024, $i), 3).' '.$unit[$i].')';

        $out .= "\n<b>PEAK MEMORY USAGE:</b> ".$r->mem_peak.' bytes (';
        $i = floor(log($r->mem_peak, 1024));
        $out .= round($r->mem_peak / pow(1024, $i), 3).' '.$unit[$i].')';
        $i = floor(log($r->mem_peak_real, 1024));
        $out .= ' (real '.round($r->mem_peak_real / pow(1024, $i), 3).' '.$unit[$i].')';

        return '<pre>'.$out.'</pre>';
    }
}
