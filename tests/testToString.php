<?php
/**
 * test object->__toString()
 * 
 * (Just a proof of concept test, because I can't use a test suite here)
 */

require_once '../Kint.class.php';

class Car
{
    protected $brand;
    
    function __construct($brand)
    {
        $this->brand = $brand;
    }
    
    public function __toString()
    {
        return $this->brand;
    }
}

$volvo = new Car('Volvo');

d($volvo);

// it's benefit is mainly here: identify the objects when they are collapsed
// (open the array not by clicking the [+], but by clicking the array header row) 
$array = array(new Car('Volvo'),
               new Car('Porsche'),
               new Car('Toyota'),
               );

d($array);

