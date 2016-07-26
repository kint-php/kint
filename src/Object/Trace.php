<?php

class Kint_Object_Trace extends Kint_Object
{
    public function renderType()
    {
        return 'Debug Backtrace';
    }

    public function renderSize()
    {
        return count($this->value_representation->contents);
    }
}
