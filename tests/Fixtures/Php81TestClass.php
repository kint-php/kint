<?php

namespace Kint\Test\Fixtures;

use X;
use Y;

class Php81TestClass
{
    public readonly string $a;
    private readonly string $b;

    public function __construct(string $a)
    {
        $this->a = $a;
        $this->b = $a.$a;
    }

    public function typeHints(X & Y $p1)
    {
    }
}
