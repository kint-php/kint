<?php

namespace Kint\Test\Fixtures;

use DateTime;

class BadDateTimeClass extends DateTime
{
    private function __clone()
    {
    }
}
