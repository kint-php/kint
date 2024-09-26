<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jonathan Vollebregt (jnvsor@gmail.com), Rokas Šleinius (raveren@gmail.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Kint\Parser;

use __PHP_Incomplete_Class;
use DomainException;
use Exception;
use InvalidArgumentException;
use Kint\Utils;
use Kint\Zval\BlobValue;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\ResourceValue;
use Kint\Zval\Value;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use ReflectionReference;

/**
 * @psalm-type ParserTrigger int-mask-of<Parser::TRIGGER_*>
 */
class Parser
{
    /**
     * Plugin triggers.
     *
     * These are constants indicating trigger points for plugins
     *
     * BEGIN: Before normal parsing
     * SUCCESS: After successful parsing
     * RECURSION: After parsing cancelled by recursion
     * DEPTH_LIMIT: After parsing cancelled by depth limit
     * COMPLETE: SUCCESS | RECURSION | DEPTH_LIMIT
     *
     * While a plugin's getTriggers may return any of these only one should
     * be given to the plugin when PluginInterface::parse is called
     */
    public const TRIGGER_NONE = 0;
    public const TRIGGER_BEGIN = 1 << 0;
    public const TRIGGER_SUCCESS = 1 << 1;
    public const TRIGGER_RECURSION = 1 << 2;
    public const TRIGGER_DEPTH_LIMIT = 1 << 3;
    public const TRIGGER_COMPLETE = self::TRIGGER_SUCCESS | self::TRIGGER_RECURSION | self::TRIGGER_DEPTH_LIMIT;

    /** @psalm-var ?class-string */
    protected ?string $caller_class;
    protected int $depth_limit = 0;
    protected string $marker;
    protected array $object_hashes = [];
    protected bool $parse_break = false;
    protected array $plugins = [];

    /**
     * @param int     $depth_limit Maximum depth to parse data
     * @param ?string $caller      Caller class name
     *
     * @psalm-param ?class-string $caller
     */
    public function __construct(int $depth_limit = 0, ?string $caller = null)
    {
        $this->marker = "kint\0".\random_bytes(16);

        $this->depth_limit = $depth_limit;
        $this->caller_class = $caller;
    }

    /**
     * Set the caller class.
     *
     * @psalm-param ?class-string $caller
     */
    public function setCallerClass(?string $caller = null): void
    {
        $this->noRecurseCall();

        $this->caller_class = $caller;
    }

    public function getCallerClass(): ?string
    {
        return $this->caller_class;
    }

    /**
     * Set the depth limit.
     *
     * @param int $depth_limit Maximum depth to parse data, 0 for none
     */
    public function setDepthLimit(int $depth_limit = 0): void
    {
        $this->noRecurseCall();

        $this->depth_limit = $depth_limit;
    }

    public function getDepthLimit(): int
    {
        return $this->depth_limit;
    }

    /**
     * Parses a variable into a Kint object structure.
     *
     * @param mixed &$var The input variable
     * @param Value $o    The base object
     */
    public function parse(&$var, Value $o): Value
    {
        $o->type = \strtolower(\gettype($var));

        if (!$this->applyPlugins($var, $o, self::TRIGGER_BEGIN)) {
            return $o;
        }

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
            case 'unknown type':
            case 'resource (closed)':
            default:
                return $this->parseResourceClosed($var, $o);
        }
    }

    public function addPlugin(PluginInterface $p): bool
    {
        if (!$types = $p->getTypes()) {
            return false;
        }

        if (!$triggers = $p->getTriggers()) {
            return false;
        }

        $p->setParser($this);

        foreach ($types as $type) {
            $this->plugins[$type] ??= [
                self::TRIGGER_BEGIN => [],
                self::TRIGGER_SUCCESS => [],
                self::TRIGGER_RECURSION => [],
                self::TRIGGER_DEPTH_LIMIT => [],
            ];

            foreach ($this->plugins[$type] as $trigger => &$pool) {
                if ($triggers & $trigger) {
                    $pool[] = $p;
                }
            }
        }

        return true;
    }

    public function clearPlugins(): void
    {
        $this->plugins = [];
    }

    public function haltParse(): void
    {
        $this->parse_break = true;
    }

    public function childHasPath(InstanceValue $parent, Value $child): bool
    {
        if (__PHP_Incomplete_Class::class === $parent->classname) {
            return false;
        }

        if ('object' === $parent->type && (null !== $parent->access_path || $child->static || $child->const)) {
            if (Value::ACCESS_PUBLIC === $child->access) {
                return true;
            }

            if (Value::ACCESS_PRIVATE === $child->access && $this->caller_class) {
                if ($this->caller_class === $child->owner_class) {
                    return true;
                }
            } elseif (Value::ACCESS_PROTECTED === $child->access && $this->caller_class) {
                if (null === $child->owner_class) {
                    throw new InvalidArgumentException('Value is protected without an owner class');
                }

                if (\is_a($this->caller_class, $child->owner_class, true)) {
                    return true;
                }

                if (\is_a($child->owner_class, $this->caller_class, true)) {
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
    public function getCleanArray(array $array): array
    {
        unset($array[$this->marker]);

        return $array;
    }

    protected function noRecurseCall(): void
    {
        $bt = \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);

        $caller_frame = [
            'function' => __FUNCTION__,
        ];

        while (isset($bt[0]['object']) && $bt[0]['object'] === $this) {
            $caller_frame = \array_shift($bt);
        }

        foreach ($bt as $frame) {
            if (isset($frame['object']) && $frame['object'] === $this) {
                throw new DomainException(__CLASS__.'::'.$caller_frame['function'].' cannot be called from inside a parse');
            }
        }
    }

    /**
     * @param null|bool|float|int &$var
     */
    private function parseGeneric(&$var, Value $o): Value
    {
        $rep = new Representation('Contents');
        $rep->contents = $var;
        $rep->implicit_label = true;
        $o->addRepresentation($rep);
        $o->value = $rep;

        $this->applyPlugins($var, $o, self::TRIGGER_SUCCESS);

        return $o;
    }

    /**
     * Parses a string into a Kint BlobValue structure.
     *
     * @param string &$var The input variable
     * @param Value  $o    The base object
     */
    private function parseString(string &$var, Value $o): Value
    {
        $string = new BlobValue($o->name);
        $string->transplant($o);
        $string->encoding = BlobValue::detectEncoding($var);
        $string->size = \strlen($var);

        $rep = new Representation('Contents');
        $rep->contents = $var;
        $rep->implicit_label = true;

        $string->addRepresentation($rep);
        $string->value = $rep;

        $this->applyPlugins($var, $string, self::TRIGGER_SUCCESS);

        return $string;
    }

    /**
     * Parses an array into a Kint object structure.
     *
     * @param array &$var The input variable
     * @param Value $o    The base object
     */
    private function parseArray(array &$var, Value $o): Value
    {
        $array = new Value($o->name);
        $array->transplant($o);
        $array->size = \count($var);

        if (isset($var[$this->marker])) {
            --$array->size;
            $array->hints['recursion'] = true;

            $this->applyPlugins($var, $array, self::TRIGGER_RECURSION);

            return $array;
        }

        $rep = new Representation('Contents');
        $rep->contents = [];
        $rep->implicit_label = true;
        $array->addRepresentation($rep);
        $array->value = $rep;

        if (!$array->size) {
            $this->applyPlugins($var, $array, self::TRIGGER_SUCCESS);

            return $array;
        }

        if ($this->depth_limit && $o->depth >= $this->depth_limit) {
            $array->hints['depth_limit'] = true;

            $this->applyPlugins($var, $array, self::TRIGGER_DEPTH_LIMIT);

            return $array;
        }

        // Set the marker for recursion
        $var[$this->marker] = $array->depth;

        foreach ($var as $key => $val) {
            if ($key === $this->marker) {
                continue;
            }

            $child = new Value($key);
            $child->depth = $array->depth + 1;
            $child->access = Value::ACCESS_NONE;
            $child->operator = Value::OPERATOR_ARRAY;
            $child->reference = null !== ReflectionReference::fromArrayElement($var, $key);

            if (null !== $array->access_path) {
                $child->access_path = $array->access_path.'['.\var_export($key, true).']';
            }

            $rep->contents[] = $this->parse($var[$key], $child);
        }

        $this->applyPlugins($var, $array, self::TRIGGER_SUCCESS);
        unset($var[$this->marker]);

        return $array;
    }

    /**
     * @psalm-return ReflectionProperty[]
     */
    private function getPropsOrdered(ReflectionClass $r): array
    {
        if ($parent = $r->getParentClass()) {
            $props = self::getPropsOrdered($parent);
        } else {
            $props = [];
        }

        foreach ($r->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            if ($prop->isPrivate()) {
                $props[] = $prop;
            } else {
                $props[$prop->name] = $prop;
            }
        }

        return $props;
    }

    /**
     * @codeCoverageIgnore
     *
     * @psalm-return ReflectionProperty[]
     */
    private function getPropsOrderedOld(ReflectionClass $r): array
    {
        $props = [];

        foreach ($r->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $props[] = $prop;
        }

        while ($r = $r->getParentClass()) {
            foreach ($r->getProperties(ReflectionProperty::IS_PRIVATE) as $prop) {
                if ($prop->isStatic()) {
                    continue;
                }

                $props[] = $prop;
            }
        }

        return $props;
    }

    /**
     * Parses an object into a Kint InstanceValue structure.
     *
     * @param object &$var The input variable
     * @param Value  $o    The base object
     */
    private function parseObject(&$var, Value $o): Value
    {
        $hash = \spl_object_hash($var);
        $values = (array) $var;

        $object = new InstanceValue($o->name, \get_class($var), $hash, \spl_object_id($var));
        $object->transplant($o);

        if (isset($this->object_hashes[$hash])) {
            $object->hints['recursion'] = true;

            $this->applyPlugins($var, $object, self::TRIGGER_RECURSION);

            return $object;
        }

        $this->object_hashes[$hash] = $object;

        if ($this->depth_limit && $o->depth >= $this->depth_limit) {
            $object->hints['depth_limit'] = true;

            $this->applyPlugins($var, $object, self::TRIGGER_DEPTH_LIMIT);
            unset($this->object_hashes[$hash]);

            return $object;
        }

        $reflector = new ReflectionObject($var);

        if ($reflector->isUserDefined()) {
            $object->filename = $reflector->getFileName();
            $object->startline = $reflector->getStartLine();
        }

        $rep = new Representation('Properties');
        $rep->contents = [];

        if (KINT_PHP81) {
            $props = $this->getPropsOrdered($reflector);
        } else {
            $props = $this->getPropsOrderedOld($reflector); // @codeCoverageIgnore
        }
        $object->size = \count($props);

        foreach ($props as $rprop) {
            $rprop->setAccessible(true);
            $name = $rprop->getName();

            // Casting object to array:
            // private properties show in the form "\0$owner_class_name\0$property_name";
            // protected properties show in the form "\0*\0$property_name";
            // public properties show in the form "$property_name";
            // http://www.php.net/manual/en/language.types.array.php#language.types.array.casting
            $key = $name;
            if ($rprop->isProtected()) {
                $key = "\0*\0".$name;
            } elseif ($rprop->isPrivate()) {
                $key = "\0".$rprop->getDeclaringClass()->getName()."\0".$name;
            }
            $initialized = \array_key_exists($key, $values);
            if ($key === (string) (int) $key) {
                $key = (int) $key;
            }

            $child = new Value($name);

            if (KINT_PHP84 && $rprop->isVirtual()) {
                $child->type = 'virtual';
                $child->virtual = true;
            } elseif (!$initialized) {
                $child->type = 'uninitialized';
            }

            $child->readonly = KINT_PHP81 && $rprop->isReadOnly();

            if ($rprop->isPublic()) {
                $child->access = Value::ACCESS_PUBLIC;
            } elseif ($rprop->isProtected()) {
                $child->access = Value::ACCESS_PROTECTED;
            } elseif ($rprop->isPrivate()) {
                $child->access = Value::ACCESS_PRIVATE;
            }

            $child->owner_class = $rprop->getDeclaringClass()->getName();
            $child->operator = Value::OPERATOR_OBJECT;
            $child->reference = $initialized && null !== ReflectionReference::fromArrayElement($values, $key);
            $child->depth = $object->depth + 1;

            if (KINT_PHP84 && $rprop->isDefault()) {
                $hooks = $rprop->getHooks();
                if (isset($hooks['get'])) {
                    $child->hooks |= Value::HOOK_GET;
                    if ($hooks['get']->returnsReference()) {
                        $child->hooks |= Value::HOOK_GET_REF;
                    }
                }
                if (isset($hooks['set'])) {
                    $child->hooks |= Value::HOOK_SET;
                    $child->hook_set_type = (string) $rprop->getSettableType();
                    if ($child->hook_set_type !== (string) $rprop->getType()) {
                        $child->hooks |= Value::HOOK_SET_TYPE;
                    } elseif ('' === $child->hook_set_type) {
                        $child->hook_set_type = null;
                    }
                }
            }

            if ($this->childHasPath($object, $child)) {
                $child->access_path = $object->access_path;

                /**
                 * @psalm-var string $child->name
                 * We set it explicitly to string in the Value initialization
                 * because since 7.2 casts to objects cast numeric keys too
                 */
                if (Utils::isValidPhpName($child->name)) {
                    $child->access_path .= '->'.$child->name;
                } else {
                    $child->access_path .= '->{'.\var_export($child->name, true).'}';
                }
            }

            if ($initialized) {
                $rep->contents[] = $this->parse($values[$key], $child);
            } else {
                $stub = null;
                if ($this->applyPlugins($stub, $child, self::TRIGGER_BEGIN)) {
                    $this->applyPlugins($stub, $child, self::TRIGGER_SUCCESS);
                }
                $rep->contents[] = $child;
            }
        }

        $object->addRepresentation($rep);
        $object->value = $rep;
        $this->applyPlugins($var, $object, self::TRIGGER_SUCCESS);
        unset($this->object_hashes[$hash]);

        return $object;
    }

    /**
     * Parses a resource into a Kint ResourceValue structure.
     *
     * @param resource &$var The input variable
     * @param Value    $o    The base object
     */
    private function parseResource(&$var, Value $o): Value
    {
        $resource = new ResourceValue($o->name, \get_resource_type($var));
        $resource->transplant($o);

        $this->applyPlugins($var, $resource, self::TRIGGER_SUCCESS);

        return $resource;
    }

    /**
     * Parses a closed resource into a Kint object structure.
     *
     * @param mixed &$var The input variable
     * @param Value $o    The base object
     */
    private function parseResourceClosed(&$var, Value $o): Value
    {
        $o->type = 'resource (closed)';
        $this->applyPlugins($var, $o, self::TRIGGER_SUCCESS);

        return $o;
    }

    /**
     * Applies plugins for an object type.
     *
     * @param mixed &$var    variable
     * @param Value $o       Kint object parsed so far
     * @param int   $trigger The trigger to check for the plugins
     *
     * @return bool Continue parsing
     */
    private function applyPlugins(&$var, Value &$o, int $trigger): bool
    {
        $break_stash = $this->parse_break;

        /** @psalm-var bool */
        $this->parse_break = false;

        $plugins = $this->plugins[$o->type][$trigger] ?? [];

        foreach ($plugins as $plugin) {
            try {
                $plugin->parse($var, $o, $trigger);
            } catch (Exception $e) {
                \trigger_error(
                    'An exception ('.\get_class($e).') was thrown in '.$e->getFile().' on line '.$e->getLine().' while executing Kint Parser Plugin "'.\get_class($plugin).'". Error message: '.$e->getMessage(),
                    E_USER_WARNING
                );
            }

            if ($this->parse_break) {
                $this->parse_break = $break_stash;

                return false;
            }
        }

        $this->parse_break = $break_stash;

        return true;
    }
}
