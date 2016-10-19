<?php

class Kint_Object_Representation_Microtime extends Kint_Object_Representation
{
    public $lap = null;
    public $total = null;
    public $avg = null;
    public $mem = 0;
    public $mem_real = 0;
    public $mem_peak = 0;
    public $mem_peak_real = 0;
    public $hints = array('microtime');

    public function __construct($lap = null, $total = null, $avg = null)
    {
        parent::__construct('Microtime');

        $this->lap = $lap;
        $this->total = $total;
        $this->avg = $avg;
        $this->mem = memory_get_usage();
        $this->mem_real = memory_get_usage(true);
        $this->mem_peak = memory_get_peak_usage();
        $this->mem_peak_real = memory_get_peak_usage(true);
    }
}
