<?php

namespace Kint\Parser;

use InvalidArgumentException;
use Kint\Object\BasicObject;

class ProxyPlugin extends Plugin
{
    protected $types;
    protected $triggers;
    protected $callback;

    public function __construct(array $types, $triggers, $callback)
    {
        if (!is_int($triggers)) {
            throw new InvalidArgumentException('ProxyPlugin triggers must be an int bitmask');
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException('ProxyPlugin callback must be callable');
        }

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
