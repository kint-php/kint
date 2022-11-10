<?php

namespace Kint\Test\Fixtures;

use X;
use Y;

class Php81TestClass
{
    public readonly string $a;
    protected readonly string $b;
    private readonly string $c;

    public function __construct(string $a)
    {
        $this->a = $a;
        $this->b = $a.$a;
        $this->c = $a.$a.$a;
    }

    public function typeHints(X & Y $p1)
    {
    }
}
