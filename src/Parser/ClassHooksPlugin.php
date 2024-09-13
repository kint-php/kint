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

use Kint\Zval\InstanceValue;
use Kint\Zval\MethodValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;
use ReflectionProperty;

class ClassHooksPlugin extends AbstractPlugin
{
    public static bool $verbose = false;
    /** @psalm-var array<class-string, array<string, MethodValue[]>> */
    protected static array $cache = [];
    /** @psalm-var array<class-string, array<string, MethodValue[]>> */
    protected static array $cache_verbose = [];

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        if (!KINT_PHP84) {
            return Parser::TRIGGER_NONE;
        }

        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (!$o instanceof InstanceValue) {
            return;
        }

        $props = $o->getRepresentation('properties') ?? $o->value;

        /**
         * @psalm-suppress PossiblyNullReference
         * Psalm bug #11055
         */
        if (!\is_array($props->contents ?? null) || 'properties' !== $props->getName()) {
            return;
        }

        /**
         * @psalm-suppress PossiblyNullIterator
         * Psalm bug #11055
         */
        foreach ($props->contents as $prop) {
            if (Value::HOOK_NONE === $prop->hooks) {
                continue;
            }

            if (!isset(self::$cache_verbose[$prop->owner_class][$prop->name])) {
                $ref = new ReflectionProperty($prop->owner_class, $prop->name);
                $hooks = $ref->getHooks();

                foreach ($hooks as $hook) {
                    if (!self::$verbose && false === $hook->getDocComment()) {
                        continue;
                    }

                    $m = new MethodValue($hook);
                    $m->depth = 1; // We don't have subs, but don't want search

                    self::$cache_verbose[$prop->owner_class][$prop->name][] = $m;

                    if (false !== $hook->getDocComment()) {
                        self::$cache[$prop->owner_class][$prop->name][] = $m;
                    }
                }

                self::$cache[$prop->owner_class][$prop->name] ??= [];

                if (self::$verbose) {
                    self::$cache_verbose[$prop->owner_class][$prop->name] ??= [];
                }
            }

            $cache = self::$verbose ? self::$cache_verbose : self::$cache;
            $cache = $cache[$prop->owner_class][$prop->name] ?? [];

            if (\count($cache)) {
                $r = new Representation('Hooks', 'propertyhooks');
                $r->contents = $cache;
                $prop->addRepresentation($r);
            }
        }
    }
}
