<?php

class Kint_Object_Throwable extends Kint_Object_Instance
{
    public $message;
    public $hints = array('object', 'throwable');

    public function __construct($throw)
    {
        if (!$throw instanceof Exception && (!KINT_PHP70 || !$throw instanceof Throwable)) {
            throw new InvalidArgumentException('Kint_Object_Throwable must be constructed with an Exception or a Throwable');
        }

        $this->message = $throw->getMessage();
    }

    public function getValueShort()
    {
        if (strlen($this->message)) {
            return '"'.$this->message.'"';
        }
    }
}
