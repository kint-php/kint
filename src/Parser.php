<?php

class Kint_Parser
{
    public static $plugins = array(
        'array' => array(
            'Kint_Parser_Table',
            'Kint_Parser_Trace',
        ),
        'integer' => array(
            'Kint_Parser_Timestamp',
        ),
        'object' => array(
            'Kint_Parser_Blacklist',
            'Kint_Parser_ClassMethods',
            'Kint_Parser_ClassStatics',
            'Kint_Parser_Closure',
            'Kint_Parser_DOMIterator',
            'Kint_Parser_DOMNode',
            'Kint_Parser_Iterator',
            'Kint_Parser_SimpleXMLElement',
            'Kint_Parser_SplFileInfo',
            'Kint_Parser_SplObjectStorage',
        ),
        'resource' => array(
            'Kint_Parser_Stream',
        ),
        'string' => array(
            'Kint_Parser_Base64',
            'Kint_Parser_Binary',
            'Kint_Parser_Color',
            'Kint_Parser_FsPath',
            'Kint_Parser_Json',
            'Kint_Parser_Microtime',
            'Kint_Parser_Serialize',
            'Kint_Parser_Timestamp',
            'Kint_Parser_Xml',
        ),
    );

    public $caller_class;
    public $max_depth;

    private $marker;
    private $object_hashes = array();
    private $plugins_break = false;
    private $plugin_map = array();
    private $plugin_pool = array();

    public function __construct($max_depth = false, $c = null)
    {
        $this->marker = uniqid("kint\0", true);
        $this->caller_class = $c;
        $this->max_depth = $max_depth;
    }

    public function parse(&$var, Kint_Object $o)
    {
        $o->type = strtolower(gettype($var));

        switch ($o->type) {
            case 'array':
                return $this->parseArray($var, $o);
            case 'boolean':
            case 'double':
            case 'integer':
            case 'null':
                return $this->parseGeneric($var, $o);
            case 'object':
                return $this->parseObject($var, $o);
            case 'resource':
                return $this->parseResource($var, $o);
            case 'string':
                return $this->parseString($var, $o);
            default:
                return $this->parseUnknown($var, $o);
        }
    }

    private function parseGeneric(&$var, Kint_Object $o)
    {
        $rep = new Kint_Object_Representation('Contents');
        $rep->contents = $var;
        $rep->implicit_label = true;
        $o->addRepresentation($rep);

        $this->applyPlugins($var, $o);

        return $o;
    }

    private function parseString(&$var, Kint_Object $o)
    {
        $string = $o->transplant(new Kint_Object_Blob());
        $string->encoding = Kint_Object_Blob::detectEncoding($var);
        $string->size = Kint_Object_Blob::strlen($var, $string->encoding);

        $rep = new Kint_Object_Representation('Contents');
        $rep->contents = $var;
        $rep->implicit_label = true;

        $string->addRepresentation($rep);

        $this->applyPlugins($var, $string);

        return $string;
    }

    private function parseArray(array &$var, Kint_Object $o)
    {
        $array = $o->transplant(new Kint_Object());
        $array->size = count($var);

        if (isset($var[$this->marker])) {
            --$array->size;
            $array->hints[] = 'recursion';

            $this->applyPlugins($var, $array);

            return $array;
        }

        $rep = new Kint_Object_Representation('Contents');
        $rep->implicit_label = true;
        $array->addRepresentation($rep);

        if ($array->size) {
            if ($this->max_depth && $o->depth >= $this->max_depth) {
                $array->hints[] = 'depth_limit';

                $this->applyPlugins($var, $array);

                return $array;
            }

            $var[$this->marker] = $array->depth;

            foreach ($var as $key => &$val) {
                if ($key === $this->marker) {
                    continue;
                }

                $child = new Kint_Object();
                $child->name = $key;
                $child->depth = $array->depth + 1;
                $child->access = Kint_Object::ACCESS_NONE;
                $child->operator = Kint_Object::OPERATOR_ARRAY;

                if ($array->access_path !== null) {
                    $child->access_path = $array->access_path.'['.var_export($key, true).']';
                }

                $rep->contents[$key] = $this->parse($val, $child);
            }

            $this->applyPlugins($var, $array);
            unset($var[$this->marker]);

            return $array;
        } else {
            $this->applyPlugins($var, $array);

            return $array;
        }
    }

    private function parseObject(&$var, Kint_Object $o)
    {
        if (KINT_PHP53 || function_exists('spl_object_hash')) {
            $hash = spl_object_hash($var);
        } else {
            ob_start();
            var_dump($var);
            preg_match('/#(\d+)/', ob_get_clean(), $match);
            $hash = $match[1];
        }

        $values = (array) $var;

        $object = $o->transplant(new Kint_Object_Instance());
        $object->classname = get_class($var);
        $object->hash = $hash;

        if (isset($this->object_hashes[$hash])) {
            $object->hints[] = 'recursion';

            $this->applyPlugins($var, $object);

            return $object;
        }

        $this->object_hashes[$hash] = $object;

        if ($this->max_depth && $o->depth >= $this->max_depth) {
            $object->hints[] = 'depth_limit';

            $this->applyPlugins($var, $object);
            unset($this->object_hashes[$hash]);

            return $object;
        }

        $object->size = 0;

        // ArrayObject (and maybe ArrayIterator, did not try yet) unsurprisingly
        // consist of mainly dark magic. What bothers me most, var_dump sees no
        // problem with it, and ArrayObject also uses a custom, undocumented
        // serialize function, so you can see the properties in internal functions,
        // but can never iterate some of them if the flags are not STD_PROP_LIST. Fun stuff.
        if ($var instanceof ArrayObject) {
            $ArrayObject_flags_stash = $var->getFlags();
            $var->setFlags(ArrayObject::STD_PROP_LIST);
        }

        $reflector = new ReflectionObject($var);

        if ($reflector->isUserDefined()) {
            $object->filename = $reflector->getFileName();
            $object->startline = $reflector->getStartLine();
        }

        $rep = new Kint_Object_Representation('Properties');

        // Casting the object to an array can provide more information
        // than reflection. Notably, parent classes' private properties
        // don't show with reflection's getProperties()
        foreach ($values as $key => &$val) {
            // casting object to array:
            // integer properties are inaccessible;
            // private variables have the class name prepended to the variable name;
            // protected variables have a '*' prepended to the variable name.
            // These prepended values have null bytes on either side.
            // http://www.php.net/manual/en/language.types.array.php#language.types.array.casting

            $child = new Kint_Object();
            $child->depth = $object->depth + 1;
            $child->owner_class = $object->classname;
            $child->operator = Kint_Object::OPERATOR_OBJECT;

            $split_key = explode("\0", $key);

            if (count($split_key) === 3 && $split_key[0] === '') {
                $child->name = $split_key[2];
                if ($split_key[1] === '*') {
                    $child->access = Kint_Object::ACCESS_PROTECTED;
                } else {
                    $child->access = Kint_Object::ACCESS_PRIVATE;
                    $child->owner_class = $split_key[1];
                }
            } else {
                $child->name = $key;
                $child->access = Kint_Object::ACCESS_PUBLIC;
            }

            if ($this->childHasPath($object, $child)) {
                $child->access_path = $object->access_path;

                if (preg_match('/^[A-Za-z0-9_]+$/', $child->name)) {
                    $child->access_path .= '->'.$child->name;
                } else {
                    $child->access_path .= '->{'.var_export($child->name, true).'}';
                }
            }

            $rep->contents[] = $this->parse($val, $child);
            ++$object->size;
        }

        foreach ($reflector->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->isProtected()) {
                if (array_key_exists("\0*\0".$property->name, $values)) {
                    continue;
                }
            } elseif ($property->isPrivate()) {
                if (array_key_exists("\0".$property->getDeclaringClass()->name."\0".$property->name, $values)) {
                    continue;
                }
            } elseif (array_key_exists($property->name, $values)) {
                continue;
            }

            $child = new Kint_Object();
            $child->depth = $object->depth + 1;
            $child->owner_class = $object->classname;
            $child->name = $property->name;
            $child->operator = Kint_Object::OPERATOR_OBJECT;

            if ($property->isProtected()) {
                $property->setAccessible(true);
                $child->owner_class = $property->getDeclaringClass()->name;
                $child->access = Kint_Object::ACCESS_PROTECTED;
            } elseif ($property->isPrivate()) {
                $child->owner_class = $property->getDeclaringClass()->name;
                $property->setAccessible(true);
                $child->access = Kint_Object::ACCESS_PRIVATE;
            } else {
                $child->access = Kint_Object::ACCESS_PUBLIC;
            }

            if ($this->childHasPath($object, $child)) {
                $child->access_path = $object->access_path;

                if (preg_match('/^[A-Za-z0-9_]+$/', $child->name)) {
                    $child->access_path .= '->'.$child->name;
                } else {
                    $child->access_path .= '->{'.var_export($child->name, true).'}';
                }
            }

            $val = $property->getValue($var);
            $rep->contents[] = $this->parse($val, $child);
            unset($val);
            ++$object->size;
        }

        if (isset($ArrayObject_flags_stash)) {
            $var->setFlags($ArrayObject_flags_stash);
        }

        usort($rep->contents, array('Kint_Parser', 'sortObjectProperties'));

        $object->addRepresentation($rep);
        $this->applyPlugins($var, $object);
        unset($this->object_hashes[$hash]);

        return $object;
    }

    private function parseResource(&$var, Kint_Object $o)
    {
        $resource = $o->transplant(new Kint_Object_Resource());
        $resource->resource_type = get_resource_type($var);

        $this->applyPlugins($var, $resource);

        return $resource;
    }

    private function parseUnknown(&$var, Kint_Object $o)
    {
        $o->type = 'unknown';

        $rep = new Kint_Object_Representation('Unknown');
        $rep->contents = var_export($variable, true);
        $o->addRepresentation($rep);

        $this->applyPlugins($var, $o);

        return $o;
    }

    private function applyPlugins(&$var, Kint_Object &$o)
    {
        $break_stash = $this->plugins_break;
        $this->plugins_break = false;

        $this->initPluginMap($o->type);

        foreach ($this->plugin_map[$o->type] as $plugin) {
            $plugin->parse($var, $o);

            if ($this->plugins_break) {
                $this->plugins_break = $break_stash;

                return;
            }
        }

        $this->plugins_break = $break_stash;
    }

    public function childHasPath(Kint_Object_Instance $parent, Kint_Object $child)
    {
        if ($parent->type === 'object' && ($parent->access_path !== null || $child->static || $child->const)) {
            if ($child->access === Kint_Object::ACCESS_PUBLIC) {
                return true;
            } elseif ($child->access === Kint_Object::ACCESS_PRIVATE && $this->caller_class && $this->caller_class === $child->owner_class) {
                // We can't accurately determine owner class on statics / consts below 5.3 so deny
                // the access path just to be sure. See ClassStatics for more info
                if (KINT_PHP53 || (!$child->static && !$child->const)) {
                    return true;
                }
            } elseif ($child->access === Kint_Object::ACCESS_PROTECTED && $this->caller_class) {
                if (($this->caller_class instanceof $child->owner_class) || ($child->owner_class instanceof $this->caller_class)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns an array without the recursion marker in it.
     *
     * DO NOT pass an array that has had it's marker removed back
     * into the parser, it will result in an extra recursion
     *
     * @param array $array Array potentially containing a recursion marker
     *
     * @return array Array with recursion marker removed
     */
    public function getCleanArray(array $array)
    {
        unset($array[$this->marker]);

        return $array;
    }

    public function haltPlugins()
    {
        $this->plugins_break = true;
    }

    protected function initPluginMap($pool)
    {
        if (isset($this->plugin_map[$pool])) {
            return;
        }

        $this->plugin_map[$pool] = array();

        if (!isset(self::$plugins[$pool])) {
            return;
        }

        foreach (self::$plugins[$pool] as $plugin) {
            if (is_subclass_of($plugin, 'Kint_Parser_Plugin')) {
                if (!isset($this->plugin_pool[$plugin])) {
                    $this->plugin_pool[$plugin] = new $plugin($this);
                }
                $this->plugin_map[$pool][] = $this->plugin_pool[$plugin];
            }
        }
    }

    private static function sortObjectProperties(Kint_Object $a, Kint_Object $b)
    {
        $sort = Kint_Object::sortByAccess($a, $b);
        if ($sort) {
            return $sort;
        }

        $sort = Kint_Object::sortByName($a, $b);
        if ($sort) {
            return $sort;
        }

        return Kint_Object_Instance::sortByHierarchy($a->owner_class, $b->owner_class);
    }
}
