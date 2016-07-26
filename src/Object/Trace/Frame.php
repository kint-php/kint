<?php

class Kint_Object_Trace_Frame extends Kint_Object
{
    public $trace;

    public function assignFrame(array &$frame)
    {
        $this->trace = array(
            'function' => isset($frame['function']) ? $frame['function'] : null,
            'line' => isset($frame['line']) ? $frame['line'] : null,
            'file' => isset($frame['file']) ? $frame['file'] : null,
            'class' => isset($frame['class']) ? $frame['class'] : null,
            'type' => isset($frame['type']) ? $frame['type'] : null,
            'object' => null,
            'args' => null,
        );

        if ($this->trace['class']) {
            $func = new ReflectionMethod($this->trace['class'], $this->trace['function']);
            $this->trace['function'] = new Kint_Object_Method($func);
        } else {
            $func = new ReflectionFunction($this->trace['function']);
            $this->trace['function'] = new Kint_Object_Method($func);
        }

        foreach ($this->value_representation->contents as $frame_prop) {
            if ($frame_prop->name == 'object') {
                $this->trace['object'] = $frame_prop;
                $this->trace['object']->name = null;
                $this->trace['object']->operator = Kint_Object::OPERATOR_NONE;
            }
            if ($frame_prop->name == 'args') {
                $this->trace['args'] = $frame_prop->value_representation->contents;
                foreach ($this->trace['args'] as &$arg) {
                    $arg = $arg->transplant(new Kint_Object_Trace_Arg());
                }
                foreach (array_values($this->trace['function']->parameters) as $param) {
                    if (isset($this->trace['args'][$param->position])) {
                        $this->trace['args'][$param->position]->param = $param;
                    }
                }
            }
        }

        $this->representations = array();

        if (isset($this->trace['file'], $this->trace['line']) && is_readable($this->trace['file'])) {
            $this->representations[] = new Kint_Object_Representation_Source($this->trace['file'], $this->trace['line']);
        }

        if ($this->trace['args']) {
            $args = new Kint_Object_Representation('Arguments');
            $args->contents = $this->trace['args'];
            $this->representations[] = $args;
        }

        if ($this->trace['object']) {
            $callee = new Kint_Object_Representation('object');
            $callee->label = 'Callee object ['.$this->trace['object']->classname.']';
            $callee->contents[] = $this->trace['object'];
            $this->representations[] = $callee;
        }
    }

    public function renderName()
    {
        return '';
    }

    public function renderOperator()
    {
        return '';
    }

    public function renderModifiers()
    {
        return '';
    }

    public function renderValueShort()
    {
        $out = '';
        if ($this->trace['class']) {
            $out = $this->trace['class'].$this->trace['type'];
        }

        return $out.$this->trace['function']->renderName();
    }

    public function renderType()
    {
        if (!empty($this->trace['file']) && !empty($this->trace['line'])) {
            return Kint_Object_Blob::escape(Kint::shortenPath($this->trace['file'])).':'.(int) $this->trace['line'];
        } else {
            return 'PHP internal call';
        }
    }
}
