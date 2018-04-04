<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\TraceFrameObject;
use Kint\Object\TraceObject;
use Kint\Utils;

class TracePlugin extends Plugin
{
    public static $blacklist = array('spl_autoload_call');

    public function getTypes()
    {
        return array('array');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        if (!$o->value) {
            return;
        }

        $trace = $this->parser->getCleanArray($var);

        if (count($trace) !== count($o->value->contents) || !Utils::isTrace($trace)) {
            return;
        }

        $o = $o->transplant(new TraceObject());
        $rep = $o->value;

        $old_trace = $rep->contents;

        Utils::normalizeAliases(self::$blacklist);

        $rep->contents = array();

        foreach ($old_trace as $frame) {
            $index = $frame->name;

            if (!isset($trace[$index]['function'])) {
                // Something's very very wrong here, but it's probably a plugin's fault
                continue;
            }

            if (Utils::traceFrameIsListed($trace[$index], self::$blacklist)) {
                continue;
            }

            $rep->contents[$index] = $frame->transplant(new TraceFrameObject());
            $rep->contents[$index]->assignFrame($trace[$index]);
        }

        ksort($rep->contents);
        $rep->contents = array_values($rep->contents);

        $o->clearRepresentations();
        $o->addRepresentation($rep);
        $o->size = count($rep->contents);
    }
}
