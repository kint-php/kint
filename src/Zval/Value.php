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

namespace Kint\Zval;

use Kint\Zval\Representation\Representation;

/**
 * @psalm-type ValueName = string|int
 */
class Value
{
    public const ACCESS_NONE = 0;
    public const ACCESS_PUBLIC = 1;
    public const ACCESS_PROTECTED = 2;
    public const ACCESS_PRIVATE = 3;

    public const OPERATOR_NONE = 0;
    public const OPERATOR_ARRAY = 1;
    public const OPERATOR_OBJECT = 2;
    public const OPERATOR_STATIC = 3;

    public const HOOK_NONE = 0;
    public const HOOK_GET = 1 << 0;
    public const HOOK_GET_REF = 1 << 1;
    public const HOOK_SET = 1 << 2;
    public const HOOK_SET_TYPE = 1 << 3;

    /** @psalm-var ValueName */
    public $name;
    public ?string $type = null;
    public bool $readonly = false;
    public bool $static = false;
    public bool $const = false;
    /** @psalm-var self::ACCESS_* */
    public int $access = self::ACCESS_NONE;
    /** @psalm-var ?class-string */
    public ?string $owner_class = null;
    public ?string $access_path = null;
    /** @psalm-var self::OPERATOR_* */
    public int $operator = self::OPERATOR_NONE;
    public bool $reference = false;
    public bool $virtual = false;
    /** @psalm-var int-mask-of<self::HOOK_*> */
    public int $hooks = self::HOOK_NONE;
    public ?string $hook_set_type = null;
    public int $depth = 0;
    public ?int $size = null;
    /** @psalm-var array<string, true> */
    public array $hints = [];
    public ?Representation $value = null;

    /** @psalm-var Representation[] */
    protected array $representations = [];

    /** @psalm-param ValueName $name */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function addRepresentation(Representation $rep, ?int $pos = null): bool
    {
        if (isset($this->representations[$rep->getName()])) {
            return false;
        }

        if (null === $pos) {
            $this->representations[$rep->getName()] = $rep;
        } else {
            $this->representations = \array_merge(
                \array_slice($this->representations, 0, $pos),
                [$rep->getName() => $rep],
                \array_slice($this->representations, $pos)
            );
        }

        return true;
    }

    public function replaceRepresentation(Representation $rep, ?int $pos = null): void
    {
        if (null === $pos) {
            $this->representations[$rep->getName()] = $rep;
        } else {
            $this->removeRepresentation($rep);
            $this->addRepresentation($rep, $pos);
        }
    }

    /**
     * @param Representation|string $rep
     */
    public function removeRepresentation($rep): void
    {
        if ($rep instanceof Representation) {
            unset($this->representations[$rep->getName()]);
        } else { // String
            unset($this->representations[$rep]);
        }
    }

    public function getRepresentation(string $name): ?Representation
    {
        return $this->representations[$name] ?? null;
    }

    /** @psalm-return Representation[] */
    public function getRepresentations(): array
    {
        return $this->representations;
    }

    public function clearRepresentations(): void
    {
        $this->representations = [];
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getHooks(): ?string
    {
        if (self::HOOK_NONE === $this->hooks) {
            return null;
        }

        $out = '{ ';
        if ($this->hooks & self::HOOK_GET) {
            if ($this->hooks & self::HOOK_GET_REF) {
                $out .= '&';
            }
            $out .= 'get; ';
        }
        if ($this->hooks & self::HOOK_SET) {
            if ($this->hooks & self::HOOK_SET_TYPE && '' !== ($this->hook_set_type ?? '')) {
                $out .= 'set('.$this->hook_set_type.'); ';
            } else {
                $out .= 'set; ';
            }
        }
        $out .= '}';

        return $out;
    }

    /** @psalm-return ?non-empty-string */
    public function getModifiers(): ?string
    {
        $out = $this->getAccess();

        if ($this->readonly) {
            $out .= ' readonly';
        }

        if ($this->const) {
            $out .= ' const';
        }

        if ($this->static) {
            $out .= ' static';
        }

        if (null !== $out) {
            /** @psalm-var non-empty-string ltrim($out) */
            return \ltrim($out);
        }

        return null;
    }

    /** @psalm-return ?non-empty-string */
    public function getAccess(): ?string
    {
        switch ($this->access) {
            case self::ACCESS_PRIVATE:
                return 'private';
            case self::ACCESS_PROTECTED:
                return 'protected';
            case self::ACCESS_PUBLIC:
                return 'public';
        }

        return null;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    /** @psalm-return ?non-empty-string */
    public function getOperator(): ?string
    {
        switch ($this->operator) {
            case self::OPERATOR_ARRAY:
                return '=>';
            case self::OPERATOR_OBJECT:
                return '->';
            case self::OPERATOR_STATIC:
                return '::';
        }

        return null;
    }

    public function getSize(): ?string
    {
        if (isset($this->size)) {
            return (string) $this->size;
        }

        return null;
    }

    public function getValueShort(): ?string
    {
        if ($rep = $this->value) {
            if ('boolean' === $this->type) {
                return ((bool) $rep->contents) ? 'true' : 'false';
            }

            if (('integer' === $this->type || 'double' === $this->type) && \is_numeric($rep->contents)) {
                return (string) $rep->contents;
            }
        }

        return null;
    }

    public function getAccessPath(): ?string
    {
        return $this->access_path;
    }

    public function transplant(self $old): void
    {
        $this->name = $old->name;
        $this->type = $old->type;
        $this->readonly = $old->readonly;
        $this->static = $old->static;
        $this->const = $old->const;
        $this->access = $old->access;
        $this->owner_class = $old->owner_class;
        $this->access_path = $old->access_path;
        $this->operator = $old->operator;
        $this->reference = $old->reference;
        $this->virtual = $old->virtual;
        $this->hooks = $old->hooks;
        $this->hook_set_type = $old->hook_set_type;
        $this->depth = $old->depth;
        $this->size = $old->size;
        $this->hints += $old->hints;
        $this->value = $old->value;
        $this->representations += $old->representations;
    }

    public static function sortByAccess(self $a, self $b): int
    {
        static $sorts = [
            self::ACCESS_PUBLIC => 1,
            self::ACCESS_PROTECTED => 2,
            self::ACCESS_PRIVATE => 3,
            self::ACCESS_NONE => 4,
        ];

        return $sorts[$a->access] - $sorts[$b->access];
    }

    public static function sortByName(self $a, self $b): int
    {
        if ((string) $a->name === (string) $b->name) {
            return (int) \is_int($b->name) - (int) \is_int($a->name);
        }

        return \strnatcasecmp((string) $a->name, (string) $b->name);
    }
}
