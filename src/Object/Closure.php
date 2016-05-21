<?php

namespace Kint\Object;

class Closure extends Instance
{
    public $parameters = array();

    public function renderAccessPath()
    {
        if ($this->access_path !== null) {
            return parent::renderAccessPath().$this->renderParams();
        }
    }

    public function renderSize()
    {
    }

    public function renderName()
    {
        return parent::renderName().$this->renderParams();
    }

    private function renderParams()
    {
        $out = array();

        foreach ($this->parameters as $p) {
            $type = $p->renderType();
            if ($type) {
                $out[] = Blob::escape($type.' '.$p->renderName());
            } else {
                $out[] = Blob::escape($p->renderName());
            }
        }

        if ($out) {
            return ' ('.implode(', ', $out).')';
        }
    }
}
