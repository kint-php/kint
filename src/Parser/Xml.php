<?php

class Kint_Parser_Xml extends Kint_Parser_Plugin
{
    /**
     * Which method to parse the variable with.
     *
     * DOMDocument provides more information including the text between nodes,
     * however it's memory usage is very high and it takes longer to parse and
     * render. Plus it's a pain to work with. So SimpleXML is the default.
     *
     * @var string
     */
    public static $parse_method = 'SimpleXML';

    public function getTypes()
    {
        return array('string');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (substr($var, 0, 5) !== '<?xml') {
            return;
        }

        if (!method_exists(get_class($this), 'xmlTo'.self::$parse_method)) {
            return;
        }

        $xml = call_user_func(array(get_class($this), 'xmlTo'.self::$parse_method), $var, $o->access_path);

        if (empty($xml)) {
            return;
        }

        list($xml, $access_path, $name) = $xml;

        $base_obj = new Kint_Object();
        $base_obj->depth = $o->depth + 1;
        $base_obj->name = $name;
        $base_obj->access_path = $access_path;

        $r = new Kint_Object_Representation('XML');
        $r->contents = $this->parser->parse($xml, $base_obj);

        $o->addRepresentation($r, 0);
    }

    protected static function xmlToSimpleXML($var, $parent_path)
    {
        try {
            $errors = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($var);
            libxml_use_internal_errors($errors);
        } catch (Exception $e) {
            if (isset($errors)) {
                libxml_use_internal_errors($errors);
            }

            return;
        }

        if (!$xml) {
            return;
        }

        if ($parent_path === null) {
            $access_path = null;
        } else {
            $access_path = 'simplexml_load_string('.$parent_path.')';
        }

        $name = $xml->getName();

        return array($xml, $access_path, $name);
    }

    /**
     * Get the DOMDocument info.
     *
     * The documentation of DOMDocument::loadXML() states that while you can
     * call it statically, it will give an E_STRICT warning. On my system it
     * actually gives an E_DEPRECATED warning, but it works so we'll just add
     * an error-silencing '@' to the access path.
     *
     * If it errors loading then we wouldn't have gotten this far in the first place.
     *
     * @param string $var         The XML string
     * @param string $parent_path The path to the parent, in this case the XML string
     *
     * @return array The root element DOMNode, the access path, and the root element name
     */
    protected static function xmlToDOMDocument($var, $parent_path)
    {
        // There's no way to check validity in DOMDocument without making errors. For shame!
        if (!self::xmlToSimpleXML($var, $parent_path)) {
            return;
        }

        $xml = new DOMDocument();
        $xml->loadXML($var);
        $xml = $xml->firstChild;

        if ($parent_path === null) {
            $access_path = null;
        } else {
            $access_path = '@DOMDocument::loadXML('.$parent_path.')->firstChild';
        }

        $name = $xml->nodeName;

        return array($xml, $access_path, $name);
    }
}
