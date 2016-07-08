<?php

class Kint_Object_Representation_Docstring extends Kint_Object_Representation
{
    public $file = null;
    public $line = null;
    public $renderers = array('docstring');

    public function __construct($docstring, $file, $line)
    {
        parent::__construct('Docstring');

        $this->file = $file;
        $this->line = $line;
        $this->contents = $docstring;
    }
}
