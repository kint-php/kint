<?php

namespace Kint\Parser\Plugin;

use Kint\Object;

class ClassMethods extends \Kint\Parser\Plugin
{
    private static $cache = array();

    public function parse(&$var, Object &$o)
    {
        if ($o->type !== 'object') {
            return;
        }

        // Recursion or depth limit
        if (!($o instanceof Object\Instance)) {
            return;
        }

        $class = get_class($var);

        $methods = new Object\Representation('Available methods', 'methods');

        // assuming class definition will not change inside one request
        if (!isset(self::$cache[$class])) {
            $pri = $pub = $pro = array();

            $reflection = new \ReflectionClass($class);

            foreach ($reflection->getMethods() as $method) {
                $m = new Object\Method($method);
                $m->depth = $o->depth + 1;

                if ($this->parser->childHasPath($o, $m)) {
                    if ($m->name === '__construct') {
                        $m->access_path = 'new \\'.$class;
                    } elseif ($m->static) {
                        $m->access_path = '\\'.$m->owner_class.'::'.$m->name;
                        $m->operator = Object::OPERATOR_STATIC;
                    } elseif (substr($o->access_path, 0, 4) === 'new ') {
                        $m->access_path = '('.$o->access_path.')->'.$m->name;
                    } else {
                        $m->access_path = $o->access_path.'->'.$m->name;
                    }
                }

                $methods->contents[] = $m;
            }

            usort($methods->contents, array(__CLASS__, 'sort'));

            self::$cache[$class] = $methods;
        }

        if (!empty(self::$cache[$class])) {
            $o->addRepresentation(self::$cache[$class]);

            if ($contents = $o->getRepresentation('contents')) {
                $contents->label = 'Properties';
            }
        }
    }

    private static function sort(Object\Method $a, Object\Method $b)
    {
        $sort = ((int) $a->static) - ((int) $b->static);
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
