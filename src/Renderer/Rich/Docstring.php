<?php

class Kint_Renderer_Rich_Docstring extends Kint_Renderer_Rich_Plugin
{
    public function render($r)
    {
        if (!($r instanceof Kint_Object_Representation_Docstring)) {
            return false;
        }

        $docstring = array();
        foreach (explode("\n", $r->contents) as $line) {
            $docstring[] = trim($line);
        }

        $docstring = implode("\n", $docstring);

        $location = '';

        if ($r->file && $r->line) {
            if (strlen($docstring)) {
                $location = "\n\n";
            }

            $location .= '<small>Defined in '.Kint_Object_Blob::escape(Kint::shortenPath($r->file)).':'.((int) $r->line);

            if ($r->class) {
                $location .= '<br>Inherited from '.Kint_Object_Blob::escape($r->class);
            }
            $location .= '</small>';
        }

        if (strlen($docstring) === 0 && strlen($location) === 0) {
            return '';
        }

        return '<pre>'.Kint_Object_Blob::escape($docstring).$location.'</pre>';
    }
}
