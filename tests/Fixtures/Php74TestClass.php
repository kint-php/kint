<?php

namespace Kint\Test\Fixtures;

class Php74TestClass
{
    public const VALUE_1 = 1;
    public const VALUE_2 = 2;
    private const VALUE_3 = 3;
    private const VALUE_4 = 4;
    public const VALUE_ARRAY = ['contents' => '{"test":"value"}'];

    public static $value_1 = 1;
    public static $value_2 = 2;
    private static $value_3 = 3;
    private static $value_4 = 4;

    public $a = 1;
    public string $b = '2';
    public string $c;
    public $d;
    public ?string $e = null;
    public $f = null;
    public ?string $g;
    protected $prot_a = 1;
    protected string $prot_b = '2';
    protected string $prot_c;
    protected $prot_d;
    protected ?string $prot_e = null;
    protected $prot_f = null;
    protected ?string $prot_g;
    private $priv_a = 1;
    private string $priv_b = '2';
    private string $priv_c;
    private $priv_d;
    private ?string $priv_e = null;
    private $priv_f = null;
    private ?string $priv_g;

    public function test1() {}
    public static function test2() {}
    private function test5() {}
    private static function test6() {}
    public function test3() {}
    public static function test4() {}
    private function test7() {}
    private static function test8() {}
}