<?php

namespace Kint\Test\Fixtures;

class Php84ChildTestClass extends Php84TestClass
{
    public int $a {
        get => $this->a;
        set => $value * 100;
    }
}
