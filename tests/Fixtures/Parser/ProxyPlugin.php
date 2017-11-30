<?php

namespace Kint\Test\Fixtures\Parser;

use Kint\Parser\Plugin;
use Kint\Object\BasicObject;

class ProxyPlugin extends Plugin
{
    protected $types;
    protected $triggers;
    protected $callback;

    public function __construct($types, $triggers, $callback)
    {
        $this->types = $types;
        $this->triggers = $triggers;
        $this->callback = $callback;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getTriggers()
    {
        return $this->triggers;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        return call_user_func_array($this->callback, array(&$var, &$o, $trigger, $this->parser));
    }
}
