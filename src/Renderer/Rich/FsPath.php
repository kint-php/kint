<?php

class Kint_Renderer_Rich_FsPath extends Kint_Renderer_Rich_Child
{
    public function render(Kint_Object_Representation $r)
    {
        if (!($r instanceof Kint_Object_Representation_FsPath)) {
            return false;
        }

        $out = $r->contents;

        if ($r->is_link && $r->linktarget) {
            $out .= ' '.basename($r->path).' -> '.$r->linktarget;
        }

        return '<pre>'.$out.'</pre>';
    }
}
