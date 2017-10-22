<?php

namespace Kint\Object;

class ResourceObject extends BasicObject
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

    public function transplant(BasicObject $new)
    {
        $new = parent::transplant($new);
        $new->resource_type = $this->resource_type;

        return $new;
    }
}
