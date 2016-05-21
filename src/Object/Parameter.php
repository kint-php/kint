<?php

namespace Kint\Object;

class Parameter extends \Kint\Object
{
    public $type_hint = null;
    public $default;
    public $position;
    public $reference;

    public function renderType()
    {
        return Blob::escape($this->type_hint);
    }

    public function renderName()
    {
        return Blob::escape('$'.$this->name);
    }

    public function __construct(\ReflectionParameter $param)
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
                } catch (\ReflectionException $e) {
                    preg_match('/\[\s\<\w+?>\s([\w]+)/s', $param->__toString(), $matches);
                    $this->type_hint = isset($matches[1]) ? $matches[1] : '';
                }
            }
        }

        $this->reference = $param->isPassedByReference();
        $this->position = $param->getPosition();
        $this->name = $param->getName();

        if ($param->isDefaultValueAvailable()) {
            $this->default = var_export($param->getDefaultValue(), true);
        }
    }
}
