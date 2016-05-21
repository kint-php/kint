<?php

namespace Kint\Parser;

use Kint\Parser;

class Json extends Parser\Plugin
{
    public function parse(&$variable, \Kint\Object $o)
    {
        if (!is_string($variable)
            || !isset($variable{0}) || ($variable{0} !== '{' && $variable{0} !== '[')
            || ($json = json_decode($variable, true)) === null
        ) {
            return false;
        }

        $val = (array) $json;
        if (empty($val)) {
            return false;
        }

        $this->value = Parser::factory($val)->extendedValue;
        $this->type = 'JSON';
    }
}
