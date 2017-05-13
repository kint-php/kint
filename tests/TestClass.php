<?php

class TestClass
{
    public $pub;
    protected $pro;
    private $pri;

    public static $pubstat;
    protected static $prostat;
    private static $pristat;

    /**
     * This is a constructor for a TestClass with the first
     * line of the docstring split into two different lines.
     *
     * And here's some more information about it
     */
    public function __construct()
    {
        $this->pub = array('pub');
        $this->pro = array('pro');
        $this->pri = array('pri');
    }

    private static function static_method()
    {
    }

    final public function final_method()
    {
    }

    private function array_hint(array $x)
    {
    }

    private function class_hint(TestClass $x)
    {
    }

    private function ref(&$x)
    {
    }

    private function default_method($x = 1234)
    {
    }

    final protected static function mix(array &$x, TestClass $y = null, $z = array(1, 2, 3), $_ = 'string')
    {
    }
}
