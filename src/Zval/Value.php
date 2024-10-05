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

use Kint\Zval\Context\ContextInterface;
use Kint\Zval\Representation\Representation;

/**
 * @psalm-import-type ValueName from ContextInterface
 */
class Value
{
    public ?string $type = null;
    public ?int $size = null;
    public ?Representation $value = null;
    /** @psalm-var array<string, true> */
    public array $hints = [];

    /** @psalm-readonly */
    protected ContextInterface $context;
    /** @psalm-var Representation[] */
    protected array $representations = [];

    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function __clone()
    {
        $this->context = clone $this->context;
    }

    public function getContext(): ContextInterface
    {
        return $this->context;
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

    public function getDisplayName(): string
    {
        return (string) $this->context->getName();
    }

    /**
     * This is basically required to make generic Value type changing in plugins
     * easy to do, but it should be very careful. Never copy things the class or
     * its parents are instantiated with (eg. ContextInterface, spl_object_hash)
     * or you'll run into serious problems.
     */
    public function transplant(self $old): void
    {
        $this->type = $old->type;
        $this->size = $old->size;
        $this->value = $old->value;
        $this->hints += $old->hints;
        $this->representations += $old->representations;
    }
}
