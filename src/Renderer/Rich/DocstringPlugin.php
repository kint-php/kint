<?php

namespace Kint\Renderer\Rich;

use Kint\Kint;
use Kint\Object\Representation\DocstringRepresentation;
use Kint\Object\Representation\Representation;

class DocstringPlugin extends Plugin implements TabPluginInterface
{
    public function renderTab(Representation $r)
    {
        if (!($r instanceof DocstringRepresentation)) {
            return false;
        }

        $docstring = array();
        foreach (explode("\n", $r->contents) as $line) {
            $docstring[] = trim($line);
        }

        $docstring = implode("\n", $docstring);

        $location = array();

        if ($r->class) {
            $location[] = 'Inherited from '.$this->renderer->escape($r->class);
        }
        if ($r->file && $r->line) {
            $location[] = 'Defined in '.$this->renderer->escape(Kint::shortenPath($r->file)).':'.((int) $r->line);
        }

        if ($location) {
            if (strlen($docstring)) {
                $docstring .= "\n\n";
            }

            $location = '<small>'.implode("\n", $location).'</small>';
        } elseif (strlen($docstring) === 0) {
            return '';
        }

        return '<pre>'.$this->renderer->escape($docstring).$location.'</pre>';
    }
}
