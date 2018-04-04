<?php

namespace Kint\Object\Representation;

class SourceRepresentation extends Representation
{
    public $hints = array('source');
    public $source = array();
    public $filename = null;
    public $line = 0;

    public function __construct($filename, $line, $padding = 7)
    {
        parent::__construct('Source');

        $this->filename = $filename;
        $this->line = $line;

        $start_line = max($line - $padding, 1);
        $length = $line + $padding + 1 - $start_line;
        $this->source = self::getSource($filename, $start_line, $length);
        if ($this->source !== null) {
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
     * @return array|null
     */
    public static function getSource($filename, $start_line = 1, $length = null)
    {
        if (!$filename || !file_exists($filename) || !is_readable($filename)) {
            return null;
        }

        $source = preg_split("/\r\n|\n|\r/", file_get_contents($filename));
        $source = array_combine(range(1, count($source)), $source);
        $source = array_slice($source, $start_line - 1, $length, true);

        return $source;
    }
}
