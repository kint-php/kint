<?php

namespace Kint\Test\Fixtures;

interface TestInterface
{
    const VALUE = 'abcd';

    public function normalMethod();
    public static function staticMethod();
}
