<?php

class Kint_Parser_Closure extends Kint_Parser_Plugin
{
    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        if (!$var instanceof Closure) {
            return;
        }

        $o = $o->transplant(new Kint_Object_Closure());
        $o->removeRepresentation('properties');

        $closure = new ReflectionFunction($var);

        $o->filename = $closure->getFileName();
        $o->startline = $closure->getStartLine();

        foreach ($closure->getParameters() as $param) {
            $o->parameters[] = new Kint_Object_Parameter($param);
        }

        $p = new Kint_Object_Representation('Parameters');
        $p->contents = &$o->parameters;
        $o->addRepresentation($p, 0);

        $statics = array();

        if (method_exists($closure, 'getClosureThis') && $v = $closure->getClosureThis()) {
            $statics = array('this' => $v);
        }

        if (count($statics = $statics + $closure->getStaticVariables())) {
            $statics_parsed = array();

            foreach ($statics as $name => &$static) {
                $obj = Kint_Object::blank('$'.$name);
                $obj->depth = $o->depth + 1;
                $statics_parsed[$name] = $this->parser->parse($static, $obj);
                if ($statics_parsed[$name]->value === null) {
                    $statics_parsed[$name]->access_path = null;
                }
            }

            $r = new Kint_Object_Representation('Uses');
            $r->contents = $statics_parsed;
            $o->addRepresentation($r, 0);
        }
    }
}
