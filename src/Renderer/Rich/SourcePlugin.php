<?php

namespace Kint\Renderer\Rich;

use Kint\Object\Representation\Representation;
use Kint\Object\Representation\SourceRepresentation;

class SourcePlugin extends Plugin implements TabPluginInterface
{
    public function renderTab(Representation $r)
    {
        if (!($r instanceof SourceRepresentation) || empty($r->source)) {
            return false;
        }

        $source = $r->source;

        // Trim empty lines from the start and end of the source
        foreach ($source as $linenum => $line) {
            if (trim($line) || $linenum === $r->line) {
                break;
            } else {
                unset($source[$linenum]);
            }
        }

        foreach (array_reverse($source, true) as $linenum => $line) {
            if (trim($line) || $linenum === $r->line) {
                break;
            } else {
                unset($source[$linenum]);
            }
        }

        $start = '';
        $highlight = '';
        $end = '';

        foreach ($source as $linenum => $line) {
            if ($linenum < $r->line) {
                $start .= $line."\n";
            } elseif ($linenum === $r->line) {
                $highlight = '<div class="kint-highlight">'.$this->renderer->escape($line).'</div>';
            } else {
                $end .= $line."\n";
            }
        }

        $output = $this->renderer->escape($start).$highlight.$this->renderer->escape(substr($end, 0, -1));

        if ($output) {
            reset($source);

            $marker = '@@ '.((int) key($source)).','.count($source).' @@';

            return '<pre class="kint-source" data-kint-sourcerange="'.$marker.'">'.$output.'</pre>';
        }
    }
}
