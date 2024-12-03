<?php

namespace Kint\Test\Fixtures;

class Php74TestClass
{
    public const VALUE_1 = 1;
    public const VALUE_2 = 2;
    protected const VALUE_ARRAY = ['contents' => '{"test":"value"}'];
    private const VALUE_3 = 3;
    private const VALUE_4 = 4;

    public static $value_1 = 1;
    public static $value_2 = 2;
    protected static int $value_uninit;
    private static $value_3 = 3;
    private static $value_4 = 4;
    public static $value_a_pub = ['contents' => '{"test":"value"}'];
    protected static $value_a_pro = ['contents' => '{"test":"value"}'];
    private static $value_a_pri = ['contents' => '{"test":"value"}'];

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
}
