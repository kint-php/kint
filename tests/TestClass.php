<?php

class TestClass
{
    public $pub;
    protected $pro;
    private $pri;

    public static $pubstat;
    protected static $prostat;
    private static $pristat;

    public function __construct()
    {
        $this->pub = array('pub');
        $this->pro = array('pro');
        $this->pri = array('pri');
    }
}
