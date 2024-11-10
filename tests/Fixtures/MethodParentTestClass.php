<?php

namespace Kint\Test\Fixtures;

class MethodParentTestClass
{
    private function __CONSTRUCT() {}
    public static function make() {
        return new static();
    }
    public function test1() {}
    public static function test2() {}
    private function test5() {}
    private static function test6() {}
    public function test3() {}
    public static function test4() {}
    private function test7() {}
    private static function test8() {}
}
