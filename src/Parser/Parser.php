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

use DomainException;
use Exception;
use InvalidArgumentException;
use Kint\Utils;
use Kint\Zval\BlobValue;
use Kint\Zval\Context\ArrayContext;
use Kint\Zval\Context\ClassDeclaredContext;
use Kint\Zval\Context\ClassOwnedContext;
use Kint\Zval\Context\ContextInterface;
use Kint\Zval\Context\PropertyContext;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\ResourceValue;
use Kint\Zval\UninitializedValue;
use Kint\Zval\Value;
use Kint\Zval\VirtualValue;
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
    protected array $array_ref_stack = [];
    protected array $object_hashes = [];
    protected array $plugins = [];

    /**
     * @param int     $depth_limit Maximum depth to parse data
     * @param ?string $caller      Caller class name
     *
     * @psalm-param ?class-string $caller
     */
    public function __construct(int $depth_limit = 0, ?string $caller = null)
    {
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

    /** @psalm-return ?class-string */
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
     */
    public function parse(&$var, ContextInterface $c): Value
    {
        $type = \strtolower(\gettype($var));

        if ($v = $this->applyPluginsBegin($var, $c, $type)) {
            return $v;
        }

        switch ($type) {
            case 'array':
                return $this->parseArray($var, $c);
            case 'boolean':
            case 'double':
            case 'integer':
            case 'null':
                return $this->parseGeneric($var, $type, $c);
            case 'object':
                return $this->parseObject($var, $c);
            case 'resource':
                return $this->parseResource($var, $c);
            case 'string':
                return $this->parseString($var, $c);
            case 'unknown type':
            case 'resource (closed)':
            default:
                return $this->parseResourceClosed($var, $type, $c);
        }
    }

    public function addPlugin(PluginInterface $p): void
    {
        if (!$types = $p->getTypes()) {
            return;
        }

        if (!$triggers = $p->getTriggers()) {
            return;
        }

        if ($triggers & self::TRIGGER_BEGIN && !$p instanceof PluginBeginInterface) {
            throw new InvalidArgumentException('Parsers triggered on begin must implement PluginBeginInterface');
        }

        if ($triggers & self::TRIGGER_COMPLETE && !$p instanceof PluginCompleteInterface) {
            throw new InvalidArgumentException('Parsers triggered on completion must implement PluginCompleteInterface');
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
    }

    public function clearPlugins(): void
    {
        $this->plugins = [];
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
    private function parseGeneric(&$var, string $type, ContextInterface $c): Value
    {
        $v = new Value($c);
        $v->type = $type;

        $rep = new Representation('Contents');
        $rep->contents = $var;
        $rep->implicit_label = true;
        $v->addRepresentation($rep);
        $v->value = $rep;

        return $this->applyPluginsComplete($var, $v, self::TRIGGER_SUCCESS);
    }

    private function parseString(string &$var, ContextInterface $c): Value
    {
        $string = new BlobValue($c);
        $string->encoding = Utils::detectEncoding($var);
        $string->size = \strlen($var);

        $rep = new Representation('Contents');
        $rep->contents = $var;
        $rep->implicit_label = true;
        $string->addRepresentation($rep);
        $string->value = $rep;

        return $this->applyPluginsComplete($var, $string, self::TRIGGER_SUCCESS);
    }

    private function parseArray(array &$var, ContextInterface $c): Value
    {
        $array = new Value($c);
        $array->type = 'array';
        $array->size = \count($var);

        $parentRef = ReflectionReference::fromArrayElement([&$var], 0)->getId();

        if (isset($this->array_ref_stack[$parentRef])) {
            $array->hints['recursion'] = true;

            return $this->applyPluginsComplete($var, $array, self::TRIGGER_RECURSION);
        }

        $this->array_ref_stack[$parentRef] = true;

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();

        if ($array->size > 0 && $this->depth_limit && $cdepth >= $this->depth_limit) {
            $array->hints['depth_limit'] = true;

            $array = $this->applyPluginsComplete($var, $array, self::TRIGGER_DEPTH_LIMIT);

            unset($this->array_ref_stack[$parentRef]);

            return $array;
        }

        $rep = new Representation('Contents');
        $rep->contents = [];
        $rep->implicit_label = true;
        $array->addRepresentation($rep);
        $array->value = $rep;

        foreach ($var as $key => $val) {
            $child = new ArrayContext($key);
            $child->depth = $cdepth + 1;
            $child->reference = null !== ReflectionReference::fromArrayElement($var, $key);

            if (null !== $ap) {
                $child->access_path = $ap.'['.\var_export($key, true).']';
            }

            $rep->contents[] = $this->parse($var[$key], $child);
        }

        $array = $this->applyPluginsComplete($var, $array, self::TRIGGER_SUCCESS);

        unset($this->array_ref_stack[$parentRef]);

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

    private function parseObject(object &$var, ContextInterface $c): Value
    {
        $hash = \spl_object_hash($var);

        $object = new InstanceValue($c, \get_class($var), $hash, \spl_object_id($var));

        if (isset($this->object_hashes[$hash])) {
            $object->hints['recursion'] = true;

            return $this->applyPluginsComplete($var, $object, self::TRIGGER_RECURSION);
        }

        $this->object_hashes[$hash] = $object;

        $cdepth = $c->getDepth();
        $ap = $c->getAccessPath();

        if ($this->depth_limit && $cdepth >= $this->depth_limit) {
            $object->hints['depth_limit'] = true;

            $object = $this->applyPluginsComplete($var, $object, self::TRIGGER_DEPTH_LIMIT);

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

        $values = (array) $var;

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

            if ($rprop->isDefault()) {
                $child = new PropertyContext(
                    $name,
                    $rprop->getDeclaringClass()->getName(),
                    ClassDeclaredContext::ACCESS_PUBLIC
                );

                $child->readonly = KINT_PHP81 && $rprop->isReadOnly();

                if ($rprop->isProtected()) {
                    $child->access = ClassDeclaredContext::ACCESS_PROTECTED;
                } elseif ($rprop->isPrivate()) {
                    $child->access = ClassDeclaredContext::ACCESS_PRIVATE;
                }

                if (KINT_PHP84) {
                    if ($rprop->isProtectedSet()) {
                        $child->access_set = ClassDeclaredContext::ACCESS_PROTECTED;
                    } elseif ($rprop->isPrivateSet()) {
                        $child->access_set = ClassDeclaredContext::ACCESS_PRIVATE;
                    }

                    $hooks = $rprop->getHooks();
                    if (isset($hooks['get'])) {
                        $child->hooks |= PropertyContext::HOOK_GET;
                        if ($hooks['get']->returnsReference()) {
                            $child->hooks |= PropertyContext::HOOK_GET_REF;
                        }
                    }
                    if (isset($hooks['set'])) {
                        $child->hooks |= PropertyContext::HOOK_SET;

                        $child->hook_set_type = (string) $rprop->getSettableType();
                        if ($child->hook_set_type !== (string) $rprop->getType()) {
                            $child->hooks |= PropertyContext::HOOK_SET_TYPE;
                        } elseif ('' === $child->hook_set_type) {
                            $child->hook_set_type = null;
                        }
                    }
                }
            } else {
                $child = new ClassOwnedContext($name, $rprop->getDeclaringClass()->getName());
            }

            $child->reference = $initialized && null !== ReflectionReference::fromArrayElement($values, $key);
            $child->depth = $cdepth + 1;

            if (null !== $ap && $child->isAccessible($this->caller_class)) {
                /** @psalm-var string $child->name */
                if (Utils::isValidPhpName($child->name)) {
                    $child->access_path = $ap.'->'.$child->name;
                } else {
                    $child->access_path = $ap.'->{'.\var_export($child->name, true).'}';
                }
            }

            if (KINT_PHP84 && $rprop->isVirtual()) {
                $rep->contents[] = new VirtualValue($child);
            } elseif (!$initialized) {
                $rep->contents[] = new UninitializedValue($child);
            } else {
                $rep->contents[] = $this->parse($values[$key], $child);
            }
        }

        $object->addRepresentation($rep);
        $object->value = $rep;
        $object = $this->applyPluginsComplete($var, $object, self::TRIGGER_SUCCESS);
        unset($this->object_hashes[$hash]);

        return $object;
    }

    /**
     * Parses a resource into a Kint ResourceValue structure.
     *
     * @param resource &$var The input variable
     */
    private function parseResource(&$var, ContextInterface $c): Value
    {
        $resource = new ResourceValue($c, \get_resource_type($var));

        $resource = $this->applyPluginsComplete($var, $resource, self::TRIGGER_SUCCESS);

        return $resource;
    }

    /**
     * Parses a closed resource into a Kint object structure.
     *
     * @param mixed &$var The input variable
     */
    private function parseResourceClosed(&$var, string $type, ContextInterface $c): Value
    {
        $v = new Value($c);
        $v->type = 'resource (closed)';

        $v = $this->applyPluginsComplete($var, $v, self::TRIGGER_SUCCESS);

        return $v;
    }

    /**
     * Applies plugins for a yet-unparsed value.
     *
     * @param mixed &$var The input variable
     */
    private function applyPluginsBegin(&$var, ContextInterface $c, string $type): ?Value
    {
        $plugins = $this->plugins[$type][self::TRIGGER_BEGIN] ?? [];

        foreach ($plugins as $plugin) {
            try {
                if ($v = $plugin->parseBegin($var, $c)) {
                    return $v;
                }
            } catch (Exception $e) {
                \trigger_error(
                    'An exception ('.\get_class($e).') was thrown in '.$e->getFile().' on line '.$e->getLine().' while executing "'.\get_class($plugin).'"->parseBegin. Error message: '.$e->getMessage(),
                    E_USER_WARNING
                );
            }
        }

        return null;
    }

    /**
     * Applies plugins for a parsed Value.
     *
     * @param mixed &$var The input variable
     */
    private function applyPluginsComplete(&$var, Value $v, int $trigger): Value
    {
        $plugins = $this->plugins[$v->type][$trigger] ?? [];

        foreach ($plugins as $plugin) {
            try {
                $v = $plugin->parseComplete($var, $v, $trigger);
            } catch (Exception $e) {
                \trigger_error(
                    'An exception ('.\get_class($e).') was thrown in '.$e->getFile().' on line '.$e->getLine().' while executing "'.\get_class($plugin).'"->parseComplete. Error message: '.$e->getMessage(),
                    E_USER_WARNING
                );
            }
        }

        return $v;
    }
}
