<?php

class Kint_Parser_Plugin_ClassMethods extends Kint_Parser_Plugin
{
    private static $cache = array();

    public function parse(&$var, Kint_Object &$o)
    {
        if ($o->type !== 'object') {
            return;
        }

        // Recursion or depth limit
        if (!($o instanceof Kint_Object_Instance)) {
            return;
        }

        $class = get_class($var);

        $methods = new Kint_Object_Representation('Available methods', 'methods');

        // assuming class definition will not change inside one request
        if (!isset(self::$cache[$class])) {
            $pri = $pub = $pro = array();

            $reflection = new ReflectionClass($class);

            foreach ($reflection->getMethods() as $method) {
                $m = new Kint_Object_Method($method);
                $m->depth = $o->depth + 1;

                if ($this->parser->childHasPath($o, $m)) {
                    if ($m->name === '__construct') {
                        if (KINT_PHP53) {
                            $m->access_path = 'new \\'.$class;
                        } else {
                            $m->access_path = 'new '.$class;
                        }
                    } elseif ($m->static) {
                        $m->operator = Kint_Object::OPERATOR_STATIC;
                        if (KINT_PHP53) {
                            $m->access_path = '\\'.$m->owner_class.'::'.$m->name;
                        } else {
                            $m->access_path = $m->owner_class.'::'.$m->name;
                        }
                    } elseif (substr($o->access_path, 0, 4) === 'new ') {
                        $m->access_path = '('.$o->access_path.')->'.$m->name;
                    } else {
                        $m->access_path = $o->access_path.'->'.$m->name;
                    }
                }

                $methods->contents[] = $m;
            }

            usort($methods->contents, array('Kint_Parser_Plugin_ClassMethods', 'sort'));

            self::$cache[$class] = $methods;
        }

        if (!empty(self::$cache[$class])) {
            $o->addRepresentation(self::$cache[$class]);

            if ($contents = $o->getRepresentation('contents')) {
                $contents->label = 'Properties';
            }
        }
    }

    private static function sort(Kint_Object_Method $a, Kint_Object_Method $b)
    {
        $sort = ((int) $a->static) - ((int) $b->static);
        if ($sort) {
            return $sort;
        }

        $sort = Kint_Object::sortByAccess($a, $b);
        if ($sort) {
            return $sort;
        }

        return Kint_Object_Instance::sortByHierarchy($a->owner_class, $b->owner_class);
    }
}
