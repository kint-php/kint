<?php

class Kint_Object_Representation
{
    public $name;
    public $label;
    public $renderers = array();
    public $contents = array();

    public function __construct($label, $name = null)
    {
        $this->label = $label;

        if ($name === null) {
            $name = preg_replace('/[^a-z]+/', '_', strtolower($label));
        }

        $this->name = $name;
    }

    public function getLabel()
    {
        if (is_array($this->contents)) {
            return Kint_Object_Blob::escape($this->label.' ('.count($this->contents).')');
        } else {
            return Kint_Object_Blob::escape($this->label);
        }
    }
}
