<?php

class Kint_Object_Representation_Source extends Kint_Object_Representation
{
    public $name = 'source';
    public $label = 'Source';
    public $hints = array('source');
    public $source = array();
    public $filename = null;
    public $line = 0;

    public function __construct($filename, $line, $padding = 7)
    {
        $this->filename = $filename;
        $this->line = $line;

        $start_line = max($line - $padding, 1);
        $length = $line + $padding + 1 - $start_line;
        $this->source = self::getSource($filename, $start_line, $length);
        if ($this->source !== false) {
            $this->contents = implode("\n", $this->source);
        }
    }

    /**
     * Gets section of source code.
     *
     * @param string   $filename   Full path to file
     * @param int      $start_line The first line to display (1 based)
     * @param int|null $length     Amount of lines to show
     *
     * @return bool|array
     */
    public static function getSource($filename, $start_line = 1, $length = null)
    {
        if (!$filename or !is_readable($filename)) {
            return false;
        }
        $source = preg_split("/\r\n|\n|\r/", file_get_contents($filename));
        $source = array_combine(range(1, count($source)), $source);
        $source = array_slice($source, $start_line - 1, $length, true);

        return $source;
    }
}
