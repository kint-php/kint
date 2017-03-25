<?php

class Kint_Parser_ClassStatics extends Kint_Parser_Plugin
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
        $reflection = new ReflectionClass($class);

        // Constants
        // TODO: PHP 7.1 allows private consts but reflection doesn't have a way to check them yet
        if (!isset(self::$cache[$class])) {
            $consts = array();

            foreach ($reflection->getConstants() as $name => $val) {
                $const = Kint_Object::blank($name);
                $const->const = true;
                $const->depth = $o->depth + 1;
                $const->owner_class = $class;
                if (KINT_PHP53) {
                    $const->access_path = '\\'.$class.'::'.$const->name;
                } else {
                    $const->access_path = $class.'::'.$const->name;
                }
                $const->operator = Kint_Object::OPERATOR_STATIC;
                $const = $this->parser->parse($val, $const);

                $consts[] = $const;
            }

            self::$cache[$class] = $consts;
        }

        $statics = new Kint_Object_Representation('Static class properties', 'statics');
        $statics->contents = self::$cache[$class];

        // Statics

        if (!KINT_PHP53) {
            $static_map = $reflection->getStaticProperties();
        }

        foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $static) {
            $prop = new Kint_Object();
            $prop->name = '$'.$static->getName();
            $prop->depth = $o->depth + 1;
            $prop->static = true;
            $prop->operator = Kint_Object::OPERATOR_STATIC;

            if (KINT_PHP53) {
                $prop->owner_class = $static->getDeclaringClass()->name;
            } else {
                // getDeclaringClass() is broke in old PHP versions, but getProperties() will only
                // return accessible properties and we can access them through the parent class so
                // we can just put the parent class here. It's not an accurate portrayal of where
                // the static comes from, but it shows a working access path so it's good enough
                $prop->owner_class = $class;
            }

            $prop->access = Kint_Object::ACCESS_PUBLIC;
            if ($static->isProtected()) {
                $prop->access = Kint_Object::ACCESS_PROTECTED;
            } elseif ($static->isPrivate()) {
                $prop->access = Kint_Object::ACCESS_PRIVATE;
            }

            if ($this->parser->childHasPath($o, $prop)) {
                if (KINT_PHP53) {
                    $prop->access_path = '\\'.$prop->owner_class.'::'.$prop->name;
                } else {
                    $prop->access_path = $prop->owner_class.'::'.$prop->name;
                }
            }

            if (KINT_PHP53) {
                $static->setAccessible(true);
                $val = $static->getValue();
            } else {
                switch ($prop->access) {
                    case Kint_Object::ACCESS_PUBLIC:
                        $val = $static_map[$static->getName()];
                        break;
                    case Kint_Object::ACCESS_PROTECTED:
                        $val = $static_map["\0*\0".$static->getName()];
                        break;
                    case Kint_Object::ACCESS_PRIVATE:
                        $val = $static_map["\0".$class."\0".$static->getName()];
                        break;
                }
            }

            $statics->contents[] = $this->parser->parse($val, $prop);
        }

        if (empty($statics->contents)) {
            return;
        }

        usort($statics->contents, array('Kint_Parser_ClassStatics', 'sort'));

        $o->addRepresentation($statics);
    }

    private static function sort(Kint_Object $a, Kint_Object $b)
    {
        $sort = ((int) $a->const) - ((int) $b->const);
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
