<?php

class Kint_Parser_ClassMethods extends Kint_Parser_Plugin
{
    private static $cache = array();

    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Kint_Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Kint_Object &$o, $trigger)
    {
        $class = get_class($var);

        // assuming class definition will not change inside one request
        if (!isset(self::$cache[$class])) {
            $methods = array();

            $reflection = new ReflectionClass($class);

            foreach ($reflection->getMethods() as $method) {
                $methods[] = new Kint_Object_Method($method);
            }

            usort($methods, array('Kint_Parser_ClassMethods', 'sort'));

            self::$cache[$class] = $methods;
        }

        if (!empty(self::$cache[$class])) {
            $rep = new Kint_Object_Representation('Available methods', 'methods');

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

        $sort = Kint_Object_Instance::sortByHierarchy($a->owner_class, $b->owner_class);
        if ($sort) {
            return $sort;
        }

        return $a->startline - $b->startline;
    }
}
