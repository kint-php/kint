<?php

namespace Kint\Test\Fixtures;

class Php84TestClass
{
    public int $a {
        get => $this->a;
        set => $value + 1;
    }

    protected int $b {
        get => $this->b * 2;
    }

    private int $c {
        /**
         * Sets a value
         */
        set(int $value) {
            $this->c = $value - 1;
        }
    }

    private int $d {
        get => $this->a / 2;
        set(int|float $fancyPantsName) {
            $this->a = round($fancyPantsName * 2);
        }
    }

    protected int $e {
        &get => $this->b / 2;
    }

    public $f {
        set($value) {
            $this->b = $value - 1;
        }
    }

    public private(set) int $g;
    public protected(set) int $h;
    private(set) int $i;
    protected(set) int $j;
    protected private(set) int $k;

    final protected static int $l;
}
