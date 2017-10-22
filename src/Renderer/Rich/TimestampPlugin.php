<?php

namespace Kint\Renderer\Rich;

use Kint\Object\Representation\Representation;

class TimestampPlugin extends Plugin implements TabPluginInterface
{
    public function renderTab(Representation $r)
    {
        // Avoid dreaded "Timezone must be set" error
        return '<pre>'.@date('Y-m-d H:i:s', $r->contents).'</pre>';
    }
}
