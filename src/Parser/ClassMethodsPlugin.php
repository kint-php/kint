<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\InstanceObject;
use Kint\Object\MethodObject;
use Kint\Object\Representation\Representation;
use ReflectionClass;

class ClassMethodsPlugin extends Plugin
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

        // assuming class definition will not change inside one request
        if (!isset(self::$cache[$class])) {
            $methods = array();

            $reflection = new ReflectionClass($class);

            foreach ($reflection->getMethods() as $method) {
                $methods[] = new MethodObject($method);
            }

            usort($methods, array('Kint\\Parser\\ClassMethodsPlugin', 'sort'));

            self::$cache[$class] = $methods;
        }

        if (!empty(self::$cache[$class])) {
            $rep = new Representation('Available methods', 'methods');

            // Can't cache access paths
            foreach (self::$cache[$class] as $m) {
                $method = clone $m;
                $method->depth = $o->depth + 1;

                if (!$this->parser->childHasPath($o, $method)) {
                    $method->access_path = null;
                } else {
                    $method->setAccessPathFrom($o);
                }

                if ($method->owner_class !== $class) {
                    $ds = clone $method->getRepresentation('docstring');
                    $ds->class = $method->owner_class;
                    $method->replaceRepresentation($ds);
                }

                $rep->contents[] = $method;
            }

            $o->addRepresentation($rep);
        }
    }

    private static function sort(MethodObject $a, MethodObject $b)
    {
        $sort = ((int) $a->static) - ((int) $b->static);
        if ($sort) {
            return $sort;
        }

        $sort = BasicObject::sortByAccess($a, $b);
        if ($sort) {
            return $sort;
        }

        $sort = InstanceObject::sortByHierarchy($a->owner_class, $b->owner_class);
        if ($sort) {
            return $sort;
        }

        return $a->startline - $b->startline;
    }
}
