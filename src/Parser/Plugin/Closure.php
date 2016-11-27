<?php

class Kint_Parser_Plugin_Closure extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!$var instanceof Closure || !$o instanceof Kint_Object_Instance || !$this->parseChildren($o)) {
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
            foreach ($statics as $name => &$static) {
                $obj = Kint_Object::blank('$'.$name);
                $obj->depth = $o->depth + 1;
                $static = $this->parser->parse($static, $obj);
                if ($static->value_representation === null) {
                    $static->access_path = null;
                }
            }

            $r = new Kint_Object_Representation('Uses');
            $r->contents = $statics;
            $o->addRepresentation($r, 0);
        }
    }
}
