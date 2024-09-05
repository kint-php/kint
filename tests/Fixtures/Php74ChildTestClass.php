<?php

namespace Kint\Test\Fixtures;

class Php74ChildTestClass extends Php74TestClass
{
    public string $c = 'subclassed';
    private string $last = 'done';
}
