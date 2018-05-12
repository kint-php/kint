<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jonathan Vollebregt (jnvsor@gmail.com), Rokas Šleinius (raveren@gmail.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Kint\Object;

class InstanceObject extends BasicObject
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
        } elseif (!($a instanceof BasicObject) || !($b instanceof BasicObject)) {
            return 0;
        } elseif ($a instanceof self && $b instanceof self) {
            $aclass = $a->classname;
            $bclass = $b->classname;
        } else {
            return 0;
        }

        if (is_subclass_of($aclass, $bclass)) {
            return -1;
        }

        if (is_subclass_of($bclass, $aclass)) {
            return 1;
        }

        return 0;
    }

    public function transplant(BasicObject $new)
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
