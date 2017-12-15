<?php

namespace Kint\Object;

use Exception;
use InvalidArgumentException;
use Throwable;

class ThrowableObject extends InstanceObject
{
    public $message;
    public $hints = array('object', 'throwable');

    public function __construct($throw)
    {
        if (!$throw instanceof Exception && (!KINT_PHP70 || !$throw instanceof Throwable)) {
            throw new InvalidArgumentException('ThrowableObject must be constructed with a Throwable');
        }

        parent::__construct();

        $this->message = $throw->getMessage();
    }

    public function getValueShort()
    {
        if (strlen($this->message)) {
            return '"'.$this->message.'"';
        }
    }
}
