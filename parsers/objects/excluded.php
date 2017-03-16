<?php

class Kint_Objects_Excluded extends KintObject
{
    public function parse( & $variable )
    {
        // exclude certain classes as per config
        foreach(Kint::$excludeClassInstances as $className) {
            if ($variable instanceof $className) {
                $this->name = $className;
                $this->value = 'Excluded by configuration';
                return array();
            }
        }
        return false;
    }
}

