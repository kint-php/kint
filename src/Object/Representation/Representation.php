<?php

namespace Kint\Object\Representation;

class Representation
{
    public $label;
    public $implicit_label = false;
    public $hints = array();
    public $contents = array();

    protected $name;

    public function __construct($label, $name = null)
    {
        $this->label = $label;

        if ($name === null) {
            $name = $label;
        }

        $this->setName($name);
    }

    public function getLabel()
    {
        if (is_array($this->contents) && count($this->contents) > 1) {
            return $this->label.' ('.count($this->contents).')';
        } else {
            return $this->label;
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = preg_replace('/[^a-z0-9]+/', '_', strtolower($name));
    }

    public function labelIsImplicit()
    {
        return $this->implicit_label;
    }
}
