<?php

namespace Kint\Test\Fixtures;

class Php8TestClass
{
    public function typeHints(string | int $p1, ?int $p2, bool $p3 = false, string $nullable = null, string | int $nullable2 = null): ?static
    {
    }
}
