<?php

namespace Kint;

use Kint;
use ReflectionObject;

class Parser
{
    // TODO: Port/new parser plugins
    public static $plugins = array(
        //~ '\\Kint\\Parser\\Callback',
        '\\Kint\\Parser\\Plugin\\ClassMethods',
        '\\Kint\\Parser\\Plugin\\ClassStatics',
        '\\Kint\\Parser\\Plugin\\Closure',
        //~ '\\Kint\\Parser\\Color',
        //~ '\\Kint\\Parser\\FsPath',
        //~ '\\Kint\\Parser\\Json',
        '\\Kint\\Parser\\Plugin\\Microtime',
        //~ '\\Kint\\Parser\\ObjectIterable',
        //~ '\\Kint\\Parser\\Smarty',
        //~ '\\Kint\\Parser\\SplFileInfo',
        //~ '\\Kint\\Parser\\SplObjectStorage',
        //~ '\\Kint\\Parser\\StringLength',
        //~ '\\Kint\\Parser\\Table',
        //~ '\\Kint\\Parser\\Timestamp',
        //~ '\\Kint\\Parser\\Trace',
        //~ '\\Kint\\Parser\\Xml',
    );

    public $caller_class;

    private $marker;
    private $object_hashes = array();

    public function __construct($c = null)
    {
        $this->marker = uniqid("kint\0");
        $this->caller_class = $c;
    }

    public function parse(&$var, $o = null)
    {
        if ($o === null) {
            $o = new Object();
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

    private function parseGeneric(&$var, Object $o)
    {
        $rep = new Object\Representation('Contents');
        $rep->contents = $var;
        $o->addRepresentation($rep);

        return $o;
    }

    private function parseString(&$var, Object $o)
    {
        $string = $o->transplant(new Object\Blob());
        $string->encoding = Object\Blob::detectEncoding($var);
        $string->size = Object\Blob::strlen($var, $string->encoding);

        $rep = new Object\Representation('Contents');
        $rep->contents = $var;

        $string->addRepresentation($rep);

        return $string;
    }

    private function parseArray(array &$var, Object $o)
    {
        if (isset($var[$this->marker])) {
            $array = $o->transplant(new Object\Recursion());
            $array->size = count($var) - 1;

            return $array;
        }

        if (\Kint::$maxLevels && $o->depth >= \Kint::$maxLevels) {
            $array = $o->transplant(new Object\DepthLimit());
            $array->size = count($var);

            return $array;
        }

        $array = $o->transplant(new Object());
        $array->size = count($var);

        $rep = new Object\Representation('Contents');
        $rep->contents = array();

        if ($array->size) {
            $var[$this->marker] = $array->depth;

            foreach ($var as $key => &$val) {
                if ($key === $this->marker) {
                    continue;
                }

                $child = new Object();
                $child->name = $key;
                $child->depth = $array->depth + 1;
                $child->access = Object::ACCESS_NONE;
                $child->operator = Object::OPERATOR_ARRAY;

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

    private function parseObject(&$var, Object $o)
    {
        $values = (array) $var;
        $hash = spl_object_hash($var);

        if (isset($this->object_hashes[$hash])) {
            $object = $o->transplant(new Object\Recursion());
            $object->classname = get_class($var);
            $object->size = count($values);
            $object->hash = $hash;

            return $object;
        }

        if (\Kint::$maxLevels && $o->depth >= \Kint::$maxLevels) {
            $object = $o->transplant(new Object\DepthLimit());
            $object->classname = get_class($var);
            $object->size = count($values);
            $object->hash = $hash;

            return $object;
        }

        $object = $o->transplant(new Object\Instance());
        $object->classname = get_class($var);
        $object->size = 0;
        $object->hash = $hash;

        $this->object_hashes[$hash] = true;

        // ArrayObject (and maybe ArrayIterator, did not try yet) unsurprisingly
        // consist of mainly dark magic. What bothers me most, var_dump sees no
        // problem with it, and ArrayObject also uses a custom, undocumented
        // serialize function, so you can see the properties in internal functions,
        // but can never iterate some of them if the flags are not STD_PROP_LIST. Fun stuff.
        if (is_a($var, 'ArrayObject')) {
            $ArrayObject_flags_stash = $var->getFlags();
            $var->setFlags(ArrayObject::STD_PROP_LIST);
        }

        $reflector = new ReflectionObject($var);

        if ($reflector->isUserDefined()) {
            $object->filename = $reflector->getFileName();
            $object->startline = $reflector->getStartLine();
        }

        $rep = new Object\Representation('Contents');
        $rep->contents = array();

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

            $child = new Object();
            $child->depth = $object->depth + 1;
            $child->owner_class = $object->classname;
            $child->operator = Object::OPERATOR_OBJECT;

            $split_key = explode("\0", $key);

            if (count($split_key) === 3 && $split_key[0] === '') {
                $child->name = $split_key[2];
                if ($split_key[1] === '*') {
                    $child->access = Object::ACCESS_PROTECTED;
                } else {
                    $child->access = Object::ACCESS_PRIVATE;
                    $child->owner_class = $split_key[1];
                }
            } else {
                $child->name = $key;
                $child->access = Object::ACCESS_PUBLIC;
            }

            if ($this->childHasPath($object, $child)) {
                $child->access_path = $object->access_path.'->'.$child->name;

                if (substr($object->access_path, 0, 4) === 'new ') {
                    // This syntax is available from 5.4.0, but we'll show it on 5.3.0 too since
                    // it gets the point across, and there's no oneline way to do it otherwise
                    $child->access_path = '('.$object->access_path.')->'.$child->name;
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

            $child = new Object();
            $child->depth = $object->depth + 1;
            $child->owner_class = $object->classname;
            $child->name = $property->name;
            $child->operator = Object::OPERATOR_OBJECT;

            if ($property->isProtected()) {
                $property->setAccessible(true);
                $child->owner_class = $property->getDeclaringClass()->name;
                $child->access = Object::ACCESS_PROTECTED;
            } elseif ($property->isPrivate()) {
                $child->owner_class = $property->getDeclaringClass()->name;
                $property->setAccessible(true);
                $child->access = Object::ACCESS_PRIVATE;
            } else {
                $child->access = Object::ACCESS_PUBLIC;
            }

            foreach ($rep->contents as $found) {
                if ($found->access === $child->access && $found->name === $child->name && $found->owner_class === $child->owner_class) {
                    continue 2;
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

        usort($rep->contents, array(__CLASS__, 'sortObjectProperties'));

        $object->addRepresentation($rep);

        return $object;
    }

    private function parseResource(&$var, Object $o)
    {
        $resource = $o->transplant(new Object\Resource());
        $resource->resource_type = get_resource_type($var);

        if ($resource->resource_type === 'stream' && $meta = stream_get_meta_data($var)) {
            if (isset($meta['uri'])) {
                $file = $meta['uri'];

                if (stream_is_local($file)) {
                    $file = Kint::shortenPath($file);
                }

                $rep = new Object\Representation('Stream');
                $rep->contents = $file;
                $resource->addRepresentation($rep);
            }
        }

        return $resource;
    }

    private function parseUnknown(&$var, Object $o)
    {
        $o->type = 'unknown';

        $rep = new Object\Representation('Unknown');
        $rep->contents = var_export($variable, true);
        $o->addRepresentation($rep);

        return $o;
    }

    private function applyPlugins(&$var, Object &$o)
    {
        foreach (self::$plugins as $handler) {
            if (!is_subclass_of($handler, '\\Kint\\Parser\\Plugin')) {
                continue;
            }

            $parser = new $handler($this);
            $parser->parse($var, $o);
            unset($parser);
        }
    }

    public function childHasPath(Object\Instance $parent, Object $child)
    {
        if ($parent->type === 'object' && ($parent->access_path !== null || $child->static || $child->const)) {
            if ($child->access === Object::ACCESS_PUBLIC) {
                return true;
            } elseif ($this->caller_class === $parent->classname) {
                if ($child->access === Object::ACCESS_PROTECTED) {
                    return true;
                } elseif ($child->access === Object::ACCESS_PRIVATE && $child->owner_class === $parent->classname) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function sortObjectProperties(Object $a, Object $b)
    {
        $sort = Object::sortByAccess($a, $b);
        if ($sort) {
            return $sort;
        }

        $sort = Object::sortByName($a, $b);
        if ($sort) {
            return $sort;
        }

        return Object\Instance::sortByHierarchy($a->owner_class, $b->owner_class);
    }
}
