<?php

class Kint_Object_Trace_Arg extends Kint_Object
{
    public $param = null;

    public function renderName()
    {
        if ($this->param) {
            return Kint_Object_Blob::escape($this->param->renderName());
        }

        return $this->name;
    }
}
