<?php

class Kint_Renderer_Rich_Source extends Kint_Renderer_Rich_Plugin
{
    public function render($r)
    {
        if (!($r instanceof Kint_Object_Representation_Source) || empty($r->source)) {
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

            return '<pre class="kint-source" data-kint-sourcerange="@@ '.((int) key($source)).','.count($source).' @@">'.$output.'</pre>';
        }
    }
}
