<?php

class Kint_Object_Closure extends Kint_Object_Instance
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
                $out[] = Kint_Object_Blob::escape($type.' '.$p->renderName());
            } else {
                $out[] = Kint_Object_Blob::escape($p->renderName());
            }
        }

        if ($out) {
            return ' ('.implode(', ', $out).')';
        }
    }

    public function renderValueShort()
    {
        return Kint_Object_Blob::escape(Kint::shortenPath($this->filename)).':'.(int) $this->startline;
    }

    public function renderOperator()
    {
        return '';
    }
}
