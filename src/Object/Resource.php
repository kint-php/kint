<?php

class Kint_Object_Resource extends Kint_Object
{
    public $resource_type = null;

    public function getType()
    {
        if ($this->resource_type) {
            return $this->resource_type.' resource';
        } else {
            return 'resource';
        }
    }

    public function transplant(Kint_Object $new)
    {
        $new = parent::transplant($new);
        $new->resource_type = $this->resource_type;

        return $new;
    }
}
