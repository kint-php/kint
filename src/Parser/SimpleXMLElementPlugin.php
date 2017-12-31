<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\BlobObject;
use Kint\Object\Representation\Representation;
use SimpleXMLElement;

class SimpleXMLElementPlugin extends Plugin
{
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
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        if (!$var instanceof SimpleXMLElement) {
            return;
        }

        $o->hints[] = 'simplexml_element';

        if (!self::$verbose) {
            $o->removeRepresentation('properties');
            $o->removeRepresentation('iterator');
            $o->removeRepresentation('methods');
        }

        // Attributes
        $a = new Representation('Attributes');

        $base_obj = new BasicObject();
        $base_obj->depth = $o->depth;

        if ($o->access_path) {
            $base_obj->access_path = '(string) '.$o->access_path;
        }

        if ($var->attributes()) {
            $attribs = iterator_to_array($var->attributes());
            $attribs = array_map('strval', $attribs);
        } else {
            $attribs = array();
        }

        // XML attributes are by definition strings and don't have children,
        // so up the depth limit in case we're just below the limit since
        // there won't be any recursive stuff anyway.
        $a->contents = $this->parser->parseDeep($attribs, $base_obj)->value->contents;

        $o->addRepresentation($a, 0);

        // Children
        // We need to check children() separately from the values we already parsed because
        // text contents won't show up in children() but they will show up in properties.
        //
        // Why do we still need to check for attributes if we already have an attributes()
        // method? Hell if I know!
        $children = $var->children();

        if ($o->value) {
            $c = new Representation('Children');

            foreach ($o->value->contents as $value) {
                if ($value->name === '@attributes') {
                    continue;
                } elseif (isset($children->{$value->name})) {
                    $i = 0;

                    while (isset($children->{$value->name}[$i])) {
                        $base_obj = new BasicObject();
                        $base_obj->depth = $o->depth + 1;
                        $base_obj->name = $value->name;
                        if ($value->access_path) {
                            $base_obj->access_path = $value->access_path.'['.$i.']';
                        }

                        $value = $this->parser->parse($children->{$value->name}[$i], $base_obj);

                        if ($value->access_path && $value->type === 'string') {
                            $value->access_path = '(string) '.$value->access_path;
                        }

                        $c->contents[] = $value;

                        ++$i;
                    }
                }
            }

            $o->size = count($c->contents);

            if (!$o->size) {
                $o->size = null;

                if (strlen((string) $var)) {
                    $base_obj = new BlobObject();
                    $base_obj->depth = $o->depth + 1;
                    $base_obj->name = $o->name;
                    if ($o->access_path) {
                        $base_obj->access_path = '(string) '.$o->access_path;
                    }

                    $value = (string) $var;

                    $c = new Representation('Contents');
                    $c->implicit_label = true;
                    $c->contents = array($this->parser->parseDeep($value, $base_obj));
                }
            }

            $o->addRepresentation($c, 0);
        }
    }
}
