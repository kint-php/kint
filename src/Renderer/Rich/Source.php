<?php

class Kint_Renderer_Rich_Source extends Kint_Renderer_Rich_Plugin
{
    public function render($r)
    {
        if (!($r instanceof Kint_Object_Representation_Source) || empty($r->source)) {
            return false;
        }

        // set the zero-padding amount for line numbers
        $format = '% '.strlen(max(array_keys($r->source))).'d';

        // TODO: Make this use an <ol> with <li value="startline"> for easier copy pasting

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

        $output = '';

        foreach ($source as $line_number => $line) {
            $output .= '<div';

            if ($line_number === $r->line) {
                $output .= ' class="kint-highlight"';
            }

            $output .= '><span>'.sprintf($format, $line_number).'</span> ';
            $output .= Kint_Object_Blob::escape($line).'</div>';
        }

        if ($output) {
            return '<pre class="kint-source">'.$output.'</pre>';
        }
    }
}
