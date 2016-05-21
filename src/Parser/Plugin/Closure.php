<?php

namespace Kint\Parser\Plugin;

use Kint\Object;

class Closure extends \Kint\Parser\Plugin
{
    public function parse(&$var, Object &$o)
    {
        if (!$var instanceof \Closure || !$o instanceof Object\Instance) {
            return;
        }

        $o = $o->transplant(new Object\Closure());
        $o->removeRepresentation('contents');

        $closure = new \ReflectionFunction($var);

        foreach ($closure->getParameters() as $param) {
            $o->parameters[] = new Object\Parameter($param);
        }

        if ($statics = $closure->getStaticVariables()) {
            if (method_exists($closure, 'getClousureThis') && $v = $closure->getClosureThis()) {
                $statics = array_merge(array('this' => $v), $statics);
            }

            foreach ($statics as $name => &$static) {
                $obj = \Kint\Object::blank('$'.$name);
                $obj->depth = $o->depth + 1;
                $static = $this->parser->parse($static, $obj);
            }

            $r = new Object\Representation('Uses');
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

        return Blob::escape(implode(' ', $out));
    }
}
