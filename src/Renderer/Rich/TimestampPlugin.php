<?php

namespace Kint\Renderer\Rich;

use DateTime;
use DateTimeZone;
use Kint\Object\Representation\Representation;

class TimestampPlugin extends Plugin implements TabPluginInterface
{
    public function renderTab(Representation $r)
    {
        return '<pre>'.
            DateTime::createFromFormat('U', $r->contents)
                ->setTimeZone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s T').'</pre>';
    }
}
