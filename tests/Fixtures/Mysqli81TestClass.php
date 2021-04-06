<?php

namespace Kint\Test\Fixtures;

use Mysqli;

class Mysqli81TestClass extends Mysqli
{
    public string|int $affected_rows = 1234;
}
