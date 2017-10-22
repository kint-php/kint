<?php

namespace Kint\Object;

class TraceObject extends BasicObject
{
    public $hints = array('trace');

    public function getType()
    {
        return 'Debug Backtrace';
    }

    public function getSize()
    {
        if (!$this->size) {
            return 'empty';
        }

        return parent::getSize();
    }
}
