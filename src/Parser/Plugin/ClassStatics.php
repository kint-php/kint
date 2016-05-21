<?php

namespace Kint\Parser\Plugin;

use Kint\Object;

class ClassStatics extends \Kint\Parser\Plugin
{
    public function parse(&$var, Object &$o)
    {
        if (!is_object($var) || !class_exists($o->classname)) {
            return;
        }

        // Recursion or depth limit
        if (!($o instanceof Object\Instance)) {
            return;
        }

        $class = get_class($var);

        $statics = new Object\Representation('Static class properties', 'statics');

        $reflection = new \ReflectionClass($class);

        // Constants
        // TODO: PHP 7.1 allows private consts. How do I get that?
        foreach ($reflection->getConstants() as $name => $val) {
            $const = Object::blank($name);
            $const->const = true;
            $const->depth = $o->depth + 1;
            $const->owner_class = $class;
            $const->access_path = '\\'.$class.'::'.$const->name;
            $const->operator = Object::OPERATOR_STATIC;
            $const = $this->parser->parse($val, $const);

            $statics->contents[] = $const;
        }

        // Statics
        $pri = $pro = $pub = array();

        foreach ($reflection->getProperties(\ReflectionProperty::IS_STATIC) as $static) {
            $prop = Object::blank($static->getName());
            $prop->depth = $o->depth + 1;
            $prop->static = true;
            $prop->owner_class = $static->getDeclaringClass()->name;
            $prop->operator = Object::OPERATOR_STATIC;

            $prop->access = Object::ACCESS_PUBLIC;
            if ($static->isProtected()) {
                $static->setAccessible(true);
                $prop->access = Object::ACCESS_PROTECTED;
            } elseif ($static->isPrivate()) {
                $static->setAccessible(true);
                $prop->access = Object::ACCESS_PRIVATE;
            }

            if ($this->parser->childHasPath($o, $prop)) {
                $prop->access_path = '\\'.$prop->owner_class.'::$'.$prop->name;
            }

            $val = $static->getValue();
            $prop = $this->parser->parse($val, $prop);

            $statics->contents[] = $prop;
        }

        if (empty($statics->contents)) {
            return;
        }

        usort($statics->contents, array(__CLASS__, 'sort'));

        $o->addRepresentation($statics);

        if ($contents = $o->getRepresentation('contents')) {
            $contents->label = 'Properties';
        }
    }

    private static function sort(Object $a, Object $b)
    {
        $sort = ((int) $a->const) - ((int) $b->const);
        if ($sort) {
            return $sort;
        }

        $sort = Object::sortByAccess($a, $b);
        if ($sort) {
            return $sort;
        }

        return Object\Instance::sortByHierarchy($a->owner_class, $b->owner_class);
    }
}
