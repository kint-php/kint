<?php

class Kint_Object_Parameter extends Kint_Object
{
    public $type_hint = null;
    public $default = null;
    public $position = null;
    public $hints = array('parameter');

    public function getType()
    {
        return $this->type_hint;
    }

    public function getName()
    {
        return '$'.$this->name;
    }

    public function __construct(ReflectionParameter $param)
    {
        if (method_exists('ReflectionParameter', 'getType')) {
            if ($type = $param->getType()) {
                $this->type_hint = (string) $type;
            }
        } else {
            if ($param->isArray()) {
                $this->type_hint = 'array';
            } else {
                try {
                    if ($this->type_hint = $param->getClass()) {
                        $this->type_hint = $this->type_hint->name;
                    }
                } catch (ReflectionException $e) {
                    preg_match('/\[\s\<\w+?>\s([\w]+)/s', $param->__toString(), $matches);
                    $this->type_hint = isset($matches[1]) ? $matches[1] : '';
                }
            }
        }

        $this->reference = $param->isPassedByReference();
        $this->name = $param->getName();

        if (KINT_PHP523) {
            $this->position = $param->getPosition();
        }

        if ($param->isDefaultValueAvailable()) {
            $default = $param->getDefaultValue();
            switch (gettype($default)) {
                case 'NULL':
                    $this->default = 'null';
                    break;
                case 'boolean':
                    $this->default = $default ? 'true' : 'false';
                    break;
                case 'array':
                    $this->default = count($default) ? 'array(...)' : 'array()';
                    break;
                default:
                    $this->default = var_export($default, true);
                    break;
            }
        }
    }

    public function getDefault()
    {
        return $this->default;
    }
}
