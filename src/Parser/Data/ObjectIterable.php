<?php

namespace Kint\Parser\Data;

use Kint\Parser;

class ObjectIterable extends Parser
{
    protected function _parse(&$variable)
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
