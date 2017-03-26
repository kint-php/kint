<?php

class Kint_Object_Closure extends Kint_Object_Instance
{
    public $parameters = array();
    public $hints = array('object', 'callable', 'closure');

    public function getAccessPath()
    {
        if ($this->access_path !== null) {
            return parent::getAccessPath().$this->getParams();
        }
    }

    public function getSize()
    {
    }

    public function getParams()
    {
        $out = array();

        foreach ($this->parameters as $p) {
            $type = $p->getType();

            $ref = $p->reference ? '&' : '';

            if ($type) {
                $out[] = $type.' '.$ref.$p->getName();
            } else {
                $out[] = $ref.$p->getName();
            }
        }

        return implode(', ', $out);
    }
}
