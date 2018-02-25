<?php

namespace Kint\Parser;

use Exception;
use Kint\Object\BasicObject;
use Kint\Object\Representation\SourceRepresentation;
use Kint\Object\ThrowableObject;
use Throwable;

class ThrowablePlugin extends Plugin
{
    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        if (!$var instanceof Exception && (!KINT_PHP70 || !$var instanceof Throwable)) {
            return;
        }

        $o = $o->transplant(new ThrowableObject($var));

        $r = new SourceRepresentation($var->getFile(), $var->getLine());

        $o->addRepresentation($r, 0);
    }
}
