<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\InstanceObject;
use Kint\Object\Representation\Representation;
use ReflectionClass;
use ReflectionProperty;

class ClassStaticsPlugin extends Plugin
{
    private static $cache = array();

    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        $class = get_class($var);
        $reflection = new ReflectionClass($class);

        // Constants
        // TODO: PHP 7.1 allows private consts but reflection doesn't have a way to check them yet
        if (!isset(self::$cache[$class])) {
            $consts = array();

            foreach ($reflection->getConstants() as $name => $val) {
                $const = BasicObject::blank($name);
                $const->const = true;
                $const->depth = $o->depth + 1;
                $const->owner_class = $class;
                $const->access_path = '\\'.$class.'::'.$const->name;
                $const->operator = BasicObject::OPERATOR_STATIC;
                $const = $this->parser->parse($val, $const);

                $consts[] = $const;
            }

            self::$cache[$class] = $consts;
        }

        $statics = new Representation('Static class properties', 'statics');
        $statics->contents = self::$cache[$class];

        foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $static) {
            $prop = new BasicObject();
            $prop->name = '$'.$static->getName();
            $prop->depth = $o->depth + 1;
            $prop->static = true;
            $prop->operator = BasicObject::OPERATOR_STATIC;
            $prop->owner_class = $static->getDeclaringClass()->name;

            $prop->access = BasicObject::ACCESS_PUBLIC;
            if ($static->isProtected()) {
                $prop->access = BasicObject::ACCESS_PROTECTED;
            } elseif ($static->isPrivate()) {
                $prop->access = BasicObject::ACCESS_PRIVATE;
            }

            if ($this->parser->childHasPath($o, $prop)) {
                $prop->access_path = '\\'.$prop->owner_class.'::'.$prop->name;
            }

            $static->setAccessible(true);
            $static = $static->getValue();
            $statics->contents[] = $this->parser->parse($static, $prop);
        }

        if (empty($statics->contents)) {
            return;
        }

        usort($statics->contents, array('Kint\\Parser\\ClassStaticsPlugin', 'sort'));

        $o->addRepresentation($statics);
    }

    private static function sort(BasicObject $a, BasicObject $b)
    {
        $sort = ((int) $a->const) - ((int) $b->const);
        if ($sort) {
            return $sort;
        }

        $sort = BasicObject::sortByAccess($a, $b);
        if ($sort) {
            return $sort;
        }

        return InstanceObject::sortByHierarchy($a->owner_class, $b->owner_class);
    }
}
