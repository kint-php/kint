<?php

class Kint_Parser_Plugin_Closure extends Kint_Parser_Plugin
{
    public function parse(&$var, Kint_Object &$o)
    {
        if (!$var instanceof Closure || !$o instanceof Kint_Object_Instance) {
            return;
        }

        $o = $o->transplant(new Kint_Object_Closure());
        $o->removeRepresentation('contents');

        $closure = new ReflectionFunction($var);

        $o->filename = $closure->getFileName();
        $o->startline = $closure->getStartLine();

        foreach ($closure->getParameters() as $param) {
            $o->parameters[] = new Kint_Object_Parameter($param);
        }

        $p = new Kint_Object_Representation('Parameters');
        $p->contents = &$o->parameters;
        $o->addRepresentation($p, 0);

        if ($statics = $closure->getStaticVariables()) {
            if (method_exists($closure, 'getClousureThis') && $v = $closure->getClosureThis()) {
                $statics = array_merge(array('this' => $v), $statics);
            }

            foreach ($statics as $name => &$static) {
                $obj = Kint_Object::blank('$'.$name);
                $obj->depth = $o->depth + 1;
                $static = $this->parser->parse($static, $obj);
            }

            $r = new Kint_Object_Representation('Uses');
            $r->contents = $statics;
            $o->addRepresentation($r, 0);
        }
    }

    public function renderModifiers()
    {
        $out = array(
            $this->abstract ? 'abstract' : null,
            $this->final ? 'final' : null,
            $this->renderAccess(),
            $this->const ? 'const' : null,
            $this->static ? 'static' : null,
        );

        foreach ($out as $index => $word) {
            if ($word === null) {
                unset($out[$index]);
            }
        }

        return Kint_Object_Blob::escape(implode(' ', $out));
    }
}
