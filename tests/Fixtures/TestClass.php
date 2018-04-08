<?php

namespace Kint\Test\Fixtures;

class TestClass
{
    const DUMP_FILE = __FILE__;
    const DUMP_LINE = 60;

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

    private static function staticMethod()
    {
    }

    final public function finalMethod()
    {
    }

    /**
     * @param array $x
     */
    private function arrayHint(array $x)
    {
    }

    private function classHint(TestClass $x)
    {
    }

    private function ref(&$x)
    {
    }

    private function defaultMethod($x = 1234)
    {
    }

    final protected static function &mix(array &$x, TestClass $y = null, $z = array(1, 2, 3), $_ = 'string')
    {
        \Kint::dump($x, $y, $z);
        +!\Kint\Kint::dump();
        ~\d($x);
        d($x); d($y);
    }

    public function __clone()
    {
        return new self();
    }

    public function __invoke($x)
    {
        return 'woot';
    }

    public function __ToStRiNg()
    {
        return 'I am totally a string';
    }

    public function __get($param)
    {
        return 'Ouch!';
    }
}
