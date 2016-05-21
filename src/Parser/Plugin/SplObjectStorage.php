<?php

namespace Kint\Parser;

use Kint\Parser;

class SplObjectStorage extends Parser\Plugin
{
    public function parse(&$variable, \Kint\Object $o)
    {
        if (!is_object($variable) || !$variable instanceof \SplObjectStorage) {
            return false;
        }

        /* @var $variable SplObjectStorage */

        $count = $variable->count();
        if ($count === 0) {
            return false;
        }

        $variable->rewind();
        while ($variable->valid()) {
            $current = $variable->current();
            $this->value[] = Parser::factory($current);
            $variable->next();
        }

        $this->type = 'Storage contents';
        $this->size = $count;
    }
}
