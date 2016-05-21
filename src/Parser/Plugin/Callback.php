<?php

namespace Kint\Parser;

class Callback extends \Kint\Parser\Plugin
{
    public function parse(&$variable, \Kint\Object $o)
    {
        return false;
    }
}
