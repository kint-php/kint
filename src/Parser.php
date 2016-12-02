<?php

class Kint_Parser
{
    // TODO: Port/new parser plugins
    public static $plugins = array(
        //~ 'Kint_Parser_Callback',
        'Kint_Parser_Plugin_Blacklist',
        'Kint_Parser_Plugin_ClassMethods',
        'Kint_Parser_Plugin_ClassStatics',
        'Kint_Parser_Plugin_Closure',
        'Kint_Parser_Plugin_Color',
        'Kint_Parser_Plugin_FsPath',
        'Kint_Parser_Plugin_Iterator',
        'Kint_Parser_Plugin_Json',
        'Kint_Parser_Plugin_Microtime',
        'Kint_Parser_Plugin_Serialize',
        'Kint_Parser_Plugin_SimpleXMLElement',
        'Kint_Parser_Plugin_Timestamp',
        'Kint_Parser_Plugin_Trace',
        'Kint_Parser_Plugin_Xml',
        //~ 'Kint_Parser_Plugin_SplFileInfo',
        //~ 'Kint_Parser_Plugin_SplObjectStorage',
        //~ 'Kint_Parser_Plugin_StringLength',
        //~ 'Kint_Parser_Plugin_Table',
    );

    public $caller_class;
    public $max_depth;

    private $marker;
    private $object_hashes = array();
    private $plugins_break = false;

    public function __construct($max_depth = false, $c = null)
    {
        $this->marker = uniqid("kint\0");
        $this->caller_class = $c;
        $this->max_depth = $max_depth;
    }

    public function parse(&$var, Kint_Object $o = null)
    {
        if ($o === null) {
            $o = new Kint_Object();
        }

        $o->type = strtolower(gettype($var));

        switch ($o->type) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'null':
                $o = $this->parseGeneric($var, $o);
                break;
            case 'string':
            case 'array':
            case 'object':
            case 'resource':
                $o = $this->{'parse'.$o->type}($var, $o);
                break;
            default:
                $o = $this->parseUnknown($var, $o);
                break;
        }

        $this->applyPlugins($var, $o);

        return $o;
    }

    private function parseGeneric(&$var, Kint_Object $o)
    {
        $rep = new Kint_Object_Representation('Contents');
        $rep->contents = $var;
        $rep->implicit_label = true;
        $o->addRepresentation($rep);

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

        return $string;
    }

    private function parseArray(array &$var, Kint_Object $o)
    {
        $array = $o->transplant(new Kint_Object());
        $array->size = count($var);

        if (isset($var[$this->marker])) {
            --$array->size;
            $array->hints[] = 'recursion';

            return $array;
        }

        $rep = new Kint_Object_Representation('Contents');
        $rep->implicit_label = true;

        if ($array->size) {
            if ($this->max_depth && $o->depth >= $this->max_depth) {
                $array->hints[] = 'depth_limit';

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

            unset($var[$this->marker]);
        }

        $array->addRepresentation($rep);

        return $array;
    }

    private function parseObject(&$var, Kint_Object $o)
    {
        if (KINT_PHP53 || function_exists('spl_object_hash')) {
            $hash = spl_object_hash($var);
        } else {
            ob_start();
            var_dump($var);
            preg_match('/[#(\d+)]/', ob_get_clean(), $match);
            $hash = $match[1];
        }

        $values = (array) $var;

        $object = $o->transplant(new Kint_Object_Instance());
        $object->classname = get_class($var);
        $object->hash = $hash;

        if (isset($this->object_hashes[$hash])) {
            $object->hints[] = 'recursion';

            return $object;
        }

        if ($this->max_depth && $o->depth >= $this->max_depth) {
            $object->hints[] = 'depth_limit';

            return $object;
        }

        $object->size = 0;
        $this->object_hashes[$hash] = $object;

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
                if ($object->depth === 0 && substr($object->access_path, 0, 4) === 'new ') {
                    // This syntax is available from 5.4.0, but we'll show it before too since
                    // it gets the point across, and there's no oneline way to do it otherwise
                    $child->access_path = '('.$object->access_path.')';
                } else {
                    $child->access_path = $object->access_path;
                }

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

            if ($property->isProtected() && isset($values["\0*\0".$property->name])) {
                continue;
            } elseif ($property->isPrivate() && isset($values["\0".$property->getDeclaringClass()->name."\0".$property->name])) {
                continue;
            } elseif (isset($values[$property->name])) {
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
                if ($object->depth === 0 && substr($object->access_path, 0, 4) === 'new ') {
                    // This syntax is available from 5.4.0, but we'll show it before too since
                    // it gets the point across, and there's no oneline way to do it otherwise
                    $child->access_path = '('.$object->access_path.')';
                } else {
                    $child->access_path = $object->access_path;
                }

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

        unset($this->object_hashes[$hash]);

        usort($rep->contents, array('Kint_Parser', 'sortObjectProperties'));

        $object->addRepresentation($rep);

        return $object;
    }

    private function parseResource(&$var, Kint_Object $o)
    {
        $resource = $o->transplant(new Kint_Object_Resource());
        $resource->resource_type = get_resource_type($var);

        if ($resource->resource_type === 'stream' && $meta = stream_get_meta_data($var)) {
            if (isset($meta['uri'])) {
                $file = $meta['uri'];

                if (stream_is_local($file)) {
                    $file = Kint::shortenPath($file);
                }

                $rep = new Kint_Object_Representation('Stream');
                $rep->contents = $file;
                $resource->addRepresentation($rep);
            }
        }

        return $resource;
    }

    private function parseUnknown(&$var, Kint_Object $o)
    {
        $o->type = 'unknown';

        $rep = new Kint_Object_Representation('Unknown');
        $rep->contents = var_export($variable, true);
        $o->addRepresentation($rep);

        return $o;
    }

    private function applyPlugins(&$var, Kint_Object &$o)
    {
        $break_stash = $this->plugins_break;
        $this->plugins_break = false;
        $recursion = false;

        if (is_array($var) && !isset($var[$this->marker])) {
            $recursion = true;
            $var[$this->marker] = $o->depth;
        } elseif (is_object($var) && !isset($this->object_hashes[$o->hash])) {
            $recursion = true;
            $objhash = $o->hash;
            $this->object_hashes[$objhash] = $o;
        }

        foreach (self::$plugins as $handler) {
            if (!is_subclass_of($handler, 'Kint_Parser_Plugin')) {
                continue;
            }

            $parser = new $handler($this);
            $parser->parse($var, $o);
            unset($parser);

            if ($this->plugins_break) {
                break;
            }
        }

        if ($recursion) {
            if (is_array($var)) {
                unset($var[$this->marker]);
            } elseif (is_object($var)) {
                unset($this->object_hashes[$objhash]);
            }
        }

        $this->plugins_break = $break_stash;
    }

    public function childHasPath(Kint_Object_Instance $parent, Kint_Object $child)
    {
        if ($parent->type === 'object' && ($parent->access_path !== null || $child->static || $child->const)) {
            if ($child->access === Kint_Object::ACCESS_PUBLIC) {
                return true;
            } elseif ($this->caller_class === $parent->classname) {
                if ($child->access === Kint_Object::ACCESS_PROTECTED) {
                    return true;
                } elseif ($child->access === Kint_Object::ACCESS_PRIVATE && $child->owner_class === $parent->classname) {
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
