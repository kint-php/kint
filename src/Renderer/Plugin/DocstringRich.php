<?php

class Kint_Renderer_Plugin_DocstringRich extends Kint_Renderer_Plugin
{
    public function render(Kint_Object_Representation $r)
    {
        if (!($r instanceof Kint_Object_Representation_Docstring)) {
            return false;
        }

        $location = '';

        if ($r->file && $r->line){
            $location = "\n\n<small>".Kint_Object_Blob::escape($r->file).':'.((int) $r->line).'</small>';
        }

        $docstring = array();
        foreach (explode("\n", $r->contents) as $line) {
            $docstring[] = trim($line);
        }

        $docstring = implode("\n", $docstring);

        return '<pre>'.Kint_Object_Blob::escape($docstring).$location.'</pre>';
    }
}
