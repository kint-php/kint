<?php

namespace Kint\Renderer\Rich;

use Kint\Object\Representation\Representation;

class BinaryPlugin extends Plugin implements TabPluginInterface
{
    public static $line_length = 0x10;
    public static $chunk_length = 0x4;

    public function renderTab(Representation $r)
    {
        $out = '<pre>';

        $chunks = str_split($r->contents, self::$line_length);

        foreach ($chunks as $index => $chunk) {
            $out .= sprintf('%08X', $index * self::$line_length).":\t";
            $out .= implode(' ', str_split(str_pad(bin2hex($chunk), 2 * self::$line_length, ' '), self::$chunk_length));
            $out .= "\t".preg_replace('/[^\x20-\x7E]/', '.', $chunk)."\n";
        }

        $out .= '</pre>';

        return $out;
    }
}
