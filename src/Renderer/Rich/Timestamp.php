<?php

class Kint_Renderer_Rich_Timestamp extends Kint_Renderer_Rich_Plugin
{
    public function render($r)
    {
        // Avoid dreaded "Timezone must be set" error
        return '<pre>'.@date('Y-m-d H:i:s', $r->contents).'</pre>';
    }
}
