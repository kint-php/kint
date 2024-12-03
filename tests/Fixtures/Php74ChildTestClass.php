<?php

namespace Kint\Test\Fixtures;

class Php74ChildTestClass extends Php74TestClass
{
    public const VALUE_1 = 'replaced';
    public const VALUE_5 = 5;
    private const VALUE_3 = 'replaced';
    private const VALUE_6 = 6;
    public const VALUE_ARRAY_2 = ['contents' => '{"test":"value"}'];

    public static $value_1 = 'replaced';
    public static $value_5 = 5;
    private static $value_3 = 'replaced';
    private static $value_6 = 6;

    public string $c = 'subclassed';
    private string $last = 'done';
}
