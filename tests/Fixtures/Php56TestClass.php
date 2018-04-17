<?php

namespace Kint\Test\Fixtures;

class Php56TestClass
{
    const DUMP_FILE = __FILE__;
    const DUMP_LINE = 16;

    public function dumpTests()
    {
        $x = array();
        $y = array();
        $z = array();

        \Kint::dump($x, $y, ...$z);
        \Kint\Kint::dump(...$x, ...$z);
        \Kint::dump($x, $y, ...$z);\Kint::dump($x, ...$y);
    }
}
