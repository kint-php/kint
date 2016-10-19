<?php

class Kint_Object_Instance extends Kint_Object
{
    public $type = 'object';
    public $classname;
    public $hash;
    public $filename;
    public $startline;
    public $hints = array('object');

    public static function sortByHierarchy($a, $b)
    {
        if (is_string($a) && is_string($b)) {
            $aclass = $a;
            $bclass = $b;
        } elseif (!($a instanceof Kint_Object) || !($b instanceof Kint_Object)) {
            return 0;
        } elseif ($a->type === 'object' && $b->type === 'object') {
            $aclass = $a->classname;
            $bclass = $b->classname;
        }

        if (is_subclass_of($aclass, $bclass)) {
            return -1;
        }

        if (is_subclass_of($bclass, $aclass)) {
            return 1;
        }

        return 0;
    }

    public function transplant(Kint_Object $new)
    {
        $new = parent::transplant($new);
        $new->classname = $this->classname;
        $new->hash = $this->hash;
        $new->filename = $this->filename;
        $new->startline = $this->startline;

        return $new;
    }

    public function getType()
    {
        return $this->classname;
    }
}
