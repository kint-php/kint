<?php

class Kint_Object_Trace extends Kint_Object
{
    public $hints = array('trace');

    public function getType()
    {
        return 'Debug Backtrace';
    }
}
