<?php

namespace Kint\Parser;

use Closure;
use Kint\Object\BasicObject;
use Kint\Object\ClosureObject;
use Kint\Object\ParameterObject;
use Kint\Object\Representation\Representation;
use ReflectionFunction;

class ClosurePlugin extends Plugin
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
        if (!$var instanceof Closure) {
            return;
        }

        $o = $o->transplant(new ClosureObject());
        $o->removeRepresentation('properties');

        $closure = new ReflectionFunction($var);

        $o->filename = $closure->getFileName();
        $o->startline = $closure->getStartLine();

        foreach ($closure->getParameters() as $param) {
            $o->parameters[] = new ParameterObject($param);
        }

        $p = new Representation('Parameters');
        $p->contents = &$o->parameters;
        $o->addRepresentation($p, 0);

        $statics = array();

        if (method_exists($closure, 'getClosureThis') && $v = $closure->getClosureThis()) {
            $statics = array('this' => $v);
        }

        if (count($statics = $statics + $closure->getStaticVariables())) {
            $statics_parsed = array();

            foreach ($statics as $name => &$static) {
                $obj = BasicObject::blank('$'.$name);
                $obj->depth = $o->depth + 1;
                $statics_parsed[$name] = $this->parser->parse($static, $obj);
                if ($statics_parsed[$name]->value === null) {
                    $statics_parsed[$name]->access_path = null;
                }
            }

            $r = new Representation('Uses');
            $r->contents = $statics_parsed;
            $o->addRepresentation($r, 0);
        }
    }
}
