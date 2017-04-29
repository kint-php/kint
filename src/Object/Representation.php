<?php

class Kint_Object_Representation
{
    public $name;
    public $label;
    public $implicit_label = false;
    public $hints = array();
    public $contents = array();

    public function __construct($label, $name = null)
    {
        $this->label = $label;

        if ($name === null) {
            $name = preg_replace('/[^a-z0-9]+/', '_', strtolower($label));
        }

        $this->name = $name;
    }

    public function getLabel()
    {
        if (is_array($this->contents) && count($this->contents) > 1) {
            return $this->label.' ('.count($this->contents).')';
        } else {
            return $this->label;
        }
    }

    public function labelIsImplicit()
    {
        return $this->implicit_label;
    }
}
