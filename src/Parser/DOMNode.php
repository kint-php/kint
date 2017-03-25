<?php

/**
 * The DOMNode parser plugin is particularly useful as it is both the only way
 * to see inside the DOMNode without print_r, and the only way to see mixed
 * text and node inside XML (SimpleXMLElement will strip out the text).
 */
class Kint_Parser_DOMNode extends Kint_Parser_Plugin
{
    /**
     * List of properties to skip parsing.
     *
     * The properties of a DOMNode can do a *lot* of damage to debuggers. The
     * DOMNode contains not one, not two, not three, not four, not 5, not 6,
     * not 7 but 8 different ways to recurse into itself:
     * * firstChild
     * * lastChild
     * * previousSibling
     * * nextSibling
     * * ownerDocument
     * * parentNode
     * * childNodes
     * * attributes
     *
     * All of this combined: the tiny SVGs used as the caret in Kint are already
     * enough to make parsing and rendering take over a second, and send memory
     * usage over 128 megs. So we blacklist every field we don't strictly need
     * and hope that that's good enough.
     *
     * In retrospect - this is probably why print_r does the same
     *
     * @var array
     */
    public static $blacklist = array(
        'parentNode' => 'DOMNode',
        'firstChild' => 'DOMNode',
        'lastChild' => 'DOMNode',
        'previousSibling' => 'DOMNode',
        'nextSibling' => 'DOMNode',
        'ownerDocument' => 'DOMDocument',
    );

    /**
     * Show all properties and methods.
     *
     * @var bool
     */
    public static $verbose = false;

    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (!$var instanceof DOMNode) {
            return;
        }

        // Fill the properties
        // They can't be enumerated through reflection or casting,
        // so we have to trust the docs and try them one at a time
        $known_properties = array(
            'nodeValue',
            'childNodes',
            'attributes',
        );

        if (self::$verbose) {
            $known_properties = array(
                'nodeName',
                'nodeValue',
                'nodeType',
                'parentNode',
                'childNodes',
                'firstChild',
                'lastChild',
                'previousSibling',
                'nextSibling',
                'attributes',
                'ownerDocument',
                'namespaceURI',
                'prefix',
                'localName',
                'baseURI',
                'textContent',
            );
        }

        $childNodes = array();
        $attributes = array();

        $rep = $o->value;

        foreach ($known_properties as $prop) {
            $prop_obj = $this->parseProperty($o, $prop, $var);
            $rep->contents[] = $prop_obj;

            if ($prop === 'childNodes') {
                $childNodes = $prop_obj->getRepresentation('iterator');
            } elseif ($prop === 'attributes') {
                $attributes = $prop_obj->getRepresentation('iterator');
            }
        }

        if (!self::$verbose) {
            $o->removeRepresentation('methods');
            $o->removeRepresentation('properties');
        }

        // Attributes and comments and text nodes don't
        // need children or attributes of their own
        if (in_array($o->classname, array('DOMAttr', 'DOMText', 'DOMComment'))) {
            return;
        }

        // Set the attributes
        if ($attributes) {
            $a = new Kint_Object_Representation('Attributes');
            foreach ($attributes->contents as $attribute) {
                $a->contents[] = self::textualNodeToString($attribute);
            }
            $o->addRepresentation($a, 0);
        }

        // Set the children
        if ($childNodes) {
            $c = new Kint_Object_Representation('Children');

            if (count($childNodes->contents) === 1 && ($node = reset($childNodes->contents)) && in_array('depth_limit', $node->hints)) {
                $node = $node->transplant(new Kint_Object_Instance());
                $node->name = 'childNodes';
                $node->classname = 'DOMNodeList';
                $c->contents = array($node);
            } else {
                foreach ($childNodes->contents as $index => $node) {
                    // Shortcircuit text nodes to plain strings
                    if ($node->classname === 'DOMText' || $node->classname === 'DOMComment') {
                        $node = self::textualNodeToString($node);

                        // And remove them if they're empty
                        if (ctype_space($node->value->contents) || $node->value->contents === '') {
                            continue;
                        }
                    }

                    $c->contents[] = $node;
                }
            }

            $o->addRepresentation($c, 0);
        }

        if (isset($c) && count($c->contents)) {
            $o->size = count($c->contents);
        }

        if (!$o->size) {
            $o->size = null;
        }
    }

    protected function parseProperty(Kint_Object $o, $prop, &$var)
    {
        // Duplicating (And slightly optimizing) the Kint_Parser::parseObject() code here
        $base_obj = new Kint_Object();
        $base_obj->depth = $o->depth + 1;
        $base_obj->owner_class = $o->classname;
        $base_obj->name = $prop;
        $base_obj->operator = Kint_Object::OPERATOR_OBJECT;
        $base_obj->access = Kint_Object::ACCESS_PUBLIC;

        if ($o->access_path !== null) {
            $base_obj->access_path = $o->access_path;

            if (preg_match('/^[A-Za-z0-9_]+$/', $base_obj->name)) {
                $base_obj->access_path .= '->'.$base_obj->name;
            } else {
                $base_obj->access_path .= '->{'.var_export($base_obj->name, true).'}';
            }
        }

        if (!isset($var->$prop)) {
            $base_obj->type = 'null';
        } elseif (isset(self::$blacklist[$prop])) {
            $base_obj = $base_obj->transplant(new Kint_Object_Instance());
            $base_obj->hints[] = 'blacklist';
            $base_obj->classname = self::$blacklist[$prop];
        } elseif ($prop === 'attributes') {
            $depth_stash = $this->parser->max_depth;
            $this->parser->max_depth = 0;
            $base_obj = $this->parser->parse($var->$prop, $base_obj);
            $this->parser->max_depth = $depth_stash;
        } else {
            $base_obj = $this->parser->parse($var->$prop, $base_obj);
        }

        return $base_obj;
    }

    protected static function textualNodeToString(Kint_Object_Instance $o)
    {
        if (empty($o->value) || empty($o->value->contents) || empty($o->classname)) {
            return;
        }

        if (!in_array($o->classname, array('DOMText', 'DOMAttr', 'DOMComment'))) {
            return;
        }

        foreach ($o->value->contents as $property) {
            if ($property->name === 'nodeValue') {
                $ret = clone $property;
                $ret->name = $o->name;

                return $ret;
            }
        }
    }
}
