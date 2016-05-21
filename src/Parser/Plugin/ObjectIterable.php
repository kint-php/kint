<?php

namespace Kint\Parser;

use Kint\Parser;

class ObjectIterable extends Parser\Plugin
{
    public function parse(&$variable, \Kint\Object $o)
    {
        if (!is_object($variable)
            || !$variable instanceof \Traversable
            || stripos(get_class($variable), 'zend') !== false // zf2 PDO wrapper does not play nice
        ) {
            return false;
        }

        $arrayCopy = iterator_to_array($variable, true);

        if ($arrayCopy === false) {
            return false;
        }

        $this->value = Parser::factory($arrayCopy)->extendedValue;
        $this->type = 'Iterator contents';
        $this->size = count($arrayCopy);
    }
}
