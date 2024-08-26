<?php

namespace Kint\Test\Fixtures;

use InvalidArgumentException;

class BadToStringClass
{
    public function __toString()
    {
        throw new InvalidArgumentException('Bad toString');
    }
}
