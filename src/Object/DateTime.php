<?php

class Kint_Object_DateTime extends Kint_Object_Instance
{
    public $dt;

    public $hints = array('object', 'datetime');

    public function __construct(DateTime $dt)
    {
        $this->dt = clone $dt;
    }

    public function getValueShort()
    {
        $stamp = $this->dt->format('Y-m-d H:i:s');
        if (KINT_PHP522 && intval($micro = $this->dt->format('u'))) {
            $stamp .= '.'.$micro;
        }
        $stamp .= $this->dt->format('P T');

        return $stamp;
    }
}
