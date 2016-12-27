<?php

class Kint_Object_Representation_Docstring extends Kint_Object_Representation
{
    public $file = null;
    public $line = null;
    public $class = null;
    public $hints = array('docstring');

    public function __construct($docstring, $file, $line, $class = null)
    {
        parent::__construct('Docstring');

        $this->file = $file;
        $this->line = $line;
        $this->class = $class;
        $this->contents = $docstring;
    }

    /**
     * Returns the representation's docstring without surrounding comments.
     *
     * Note that this will not work flawlessly.
     *
     * On comments with whitespace after the stars the lines will begin with
     * whitespace, since we can't accurately guess how much of an indentation
     * is required.
     *
     * And on lines without stars on the left this may eat bullet points.
     *
     * Long story short: If you want the docstring read the contents. If you
     * absolutely must have it without comments (ie renderValueShort) this will
     * probably do.
     *
     * @return string Docstring with comments stripped
     */
    public function docstringWithoutComments()
    {
        $lines = preg_replace('%^\s*/\*+%', '', $this->contents);
        $lines = preg_replace('%\*+/\s*$%', '', $lines);
        $lines = explode("\n", $lines);

        foreach ($lines as $line) {
            $line = trim($line);
            $line = ltrim($line, '*');
            $string[] = $line;
        }

        $string = implode("\n", $string);

        return trim($string);
    }
}
