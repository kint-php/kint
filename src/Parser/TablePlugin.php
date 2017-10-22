<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\Representation\Representation;

class TablePlugin extends Plugin
{
    public function getTypes()
    {
        return array('array');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        if (empty($o->value->contents)) {
            return;
        }

        $array = $this->parser->getCleanArray($var);

        if (count($array) < 2) {
            return;
        }

        // Ensure this is an array of arrays and that all child arrays have the
        // same keys. We don't care about their children - if there's another
        // "table" inside we'll just make another one down the value tab
        $keys = null;
        foreach ($array as $elem) {
            if (!is_array($elem) || count($elem) < 2) {
                return;
            } elseif ($keys === null) {
                $keys = array_keys($elem);
            } elseif (array_keys($elem) !== $keys) {
                return;
            }
        }

        // Ensure none of the child arrays are recursion or depth limit. We
        // don't care if their children are since they are the table cells
        foreach ($o->value->contents as $childarray) {
            if (empty($childarray->value->contents)) {
                return;
            }
        }

        // Objects by reference for the win! We can do a copy-paste of the value
        // representation contents and just slap a new hint on there and hey
        // presto we have our table representation with no extra memory used!
        $table = new Representation('Table');
        $table->contents = $o->value->contents;
        $table->hints[] = 'table';
        $o->addRepresentation($table, 0);
    }
}
