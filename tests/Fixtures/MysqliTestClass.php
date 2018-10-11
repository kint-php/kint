<?php

namespace Kint\Test\Fixtures;

use Mysqli;

class MysqliTestClass extends Mysqli
{
    public $affected_rows = 1234;
}
