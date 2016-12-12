<?php

class Kint_Object_TraceFrame extends Kint_Object
{
    public $trace;
    public $hints = array('trace_frame');

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

        if ($this->trace['class'] && method_exists($this->trace['class'], $this->trace['function'])) {
            $func = new ReflectionMethod($this->trace['class'], $this->trace['function']);
            $this->trace['function'] = new Kint_Object_Method($func);
        } elseif (!$this->trace['class'] && function_exists($this->trace['function'])) {
            $func = new ReflectionFunction($this->trace['function']);
            $this->trace['function'] = new Kint_Object_Method($func);
        }

        foreach ($this->value->contents as $frame_prop) {
            if ($frame_prop->name === 'object') {
                $this->trace['object'] = $frame_prop;
                $this->trace['object']->name = null;
                $this->trace['object']->operator = Kint_Object::OPERATOR_NONE;
            }
            if ($frame_prop->name === 'args') {
                $this->trace['args'] = $frame_prop->value->contents;

                if (is_object($this->trace['function'])) {
                    foreach (array_values($this->trace['function']->parameters) as $param) {
                        if (isset($this->trace['args'][$param->position])) {
                            $this->trace['args'][$param->position]->name = $param->getName();
                        }
                    }
                }
            }
        }

        $this->clearRepresentations();

        if (isset($this->trace['file'], $this->trace['line']) && is_readable($this->trace['file'])) {
            $this->addRepresentation(new Kint_Object_Representation_Source($this->trace['file'], $this->trace['line']));
        }

        if ($this->trace['args']) {
            $args = new Kint_Object_Representation('Arguments');
            $args->contents = $this->trace['args'];
            $this->addRepresentation($args);
        }

        if ($this->trace['object']) {
            $callee = new Kint_Object_Representation('object');
            $callee->label = 'Callee object ['.$this->trace['object']->classname.']';
            $callee->contents[] = $this->trace['object'];
            $this->addRepresentation($callee);
        }
    }
}
