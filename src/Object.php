<?php

namespace Kint;

class Object
{
    const ACCESS_NONE = null;
    const ACCESS_PUBLIC = 'public';
    const ACCESS_PROTECTED = 'protected';
    const ACCESS_PRIVATE = 'private';

    const OPERATOR_NONE = null;
    const OPERATOR_ARRAY = '=>';
    const OPERATOR_OBJECT = '->';
    const OPERATOR_STATIC = '::';

    public $name;
    public $type;
    public $static = false;
    public $const = false;
    public $access = self::ACCESS_NONE;
    public $owner_class;
    public $access_path;
    public $operator = self::OPERATOR_NONE;
    public $size;
    public $depth = 0;
    public $representations = array();

    public function addRepresentation(Object\Representation $r, $pos = null)
    {
        if ($this->getRepresentation($r->name)) {
            return false;
        }

        if ($pos === null) {
            $this->representations[$r->name] = $r;

            return true;
        } else {
            $this->representations = array_merge(
                array_slice($this->representations, 0, $pos),
                array($r->name => $r),
                array_slice($this->representations, $pos)
            );
        }
    }

    public function replaceRepresentation(Object\Representation $rep)
    {
        foreach ($this->representations as $i => $r) {
            if ($r->name == $rep->name) {
                $this->representations[$i] = $rep;
                break;
            }
        }
    }

    public function replaceContentsOrDefault(Object\Representation $r)
    {
        if ($this->getDefaultRepresentation()->name === 'contents' && get_class($this->getDefaultRepresentation()) === 'Kint\\Object\\Representation') {
            $r->name = 'contents';
            if (empty($r->contents)) {
                $r->contents = $this->getDefaultRepresentation()->contents;
            }
            $this->replaceRepresentation($r);
        } else {
            $this->removeRepresentation($r->name);
            array_unshift($this->representations, $r);
        }
    }

    public function removeRepresentation($name)
    {
        foreach ($this->representations as $i => $r) {
            if ($r->name == $name) {
                unset($this->representations[$i]);
            }
        }
    }

    public function getDefaultRepresentation()
    {
        $rep = $this->getRepresentations();

        return reset($rep);
    }

    public function getRepresentation($name)
    {
        foreach ($this->representations as $r) {
            if ($r->name == $name) {
                return $r;
            }
        }
    }

    public function getRepresentations()
    {
        $out = array();

        foreach ($this->representations as $r) {
            if (!is_array($r->contents) || count($r->contents)) {
                $out[$r->name] = $r;
            }
        }

        return $out;
    }

    public function renderType()
    {
        return $this->type === 'object' ? $this->classname : $this->type;
    }

    public function renderModifiers()
    {
        $out = array(
            $this->renderAccess(),
            $this->const ? 'const' : null,
            $this->static ? 'static' : null,
        );

        foreach ($out as $index => $word) {
            if ($word === null) {
                unset($out[$index]);
            }
        }

        return Object\Blob::escape(implode(' ', $out));
    }

    public function renderAccess()
    {
        switch ($this->access) {
            case self::ACCESS_PRIVATE:
                return 'private';
            case self::ACCESS_PROTECTED:
                return 'protected';
            case self::ACCESS_PUBLIC:
                return 'public';
        }

        return;
    }

    public function renderName()
    {
        return Object\Blob::escape($this->name);
    }

    public function renderOperator()
    {
        if ($this->operator === self::OPERATOR_ARRAY) {
            return '=>';
        } elseif ($this->operator === self::OPERATOR_OBJECT) {
            return '->';
        } elseif ($this->operator === self::OPERATOR_STATIC) {
            return '::';
        }

        return;
    }

    public function renderSize()
    {
        return $this->size;
    }

    public function renderValueShort()
    {
        if ($this->type === 'null') {
            return 'NULL';
        } elseif ($rep = $this->getDefaultRepresentation()) {
            if ($this->type === 'boolean') {
                return $rep->contents ? 'true' : 'false';
            } elseif ($this->type !== 'array' && $this->type !== 'object' && $this->type !== 'unknown') {
                return Object\Blob::escape((string) $rep->contents);
            }
        }
    }

    public function renderAccessPath()
    {
        if ($this->depth === 0) {
            return $this->renderName();
        } else {
            return Object\Blob::escape($this->access_path);
        }
    }

    public static function blank($name = null)
    {
        $o = new self();
        $o->name = $name;
        $o->access_path = $name;

        return $o;
    }

    public function transplant(self $new)
    {
        $new->name = $this->name;
        $new->access_path = $this->access_path;
        $new->access = $this->access;
        $new->static = $this->static;
        $new->const = $this->const;
        $new->type = $this->type;
        $new->depth = $this->depth;
        $new->owner_class = $this->owner_class;
        $new->operator = $this->operator;
        $new->representations = $this->representations;

        return $new;
    }

    public static function isSequential(array $array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    public static function sortByAccess(Object $a, Object $b)
    {
        static $sorts = array(
            self::ACCESS_PUBLIC => 1,
            self::ACCESS_PROTECTED => 2,
            self::ACCESS_PRIVATE => 3,
            self::ACCESS_NONE => 4,
        );

        return $sorts[$a->access] - $sorts[$b->access];
    }

    public static function sortByName(Object $a, Object $b)
    {
        return strcmp($a->name, $b->name);
    }
}
