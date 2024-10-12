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
abstract class AbstractValue
{
    /** @psalm-readonly */
    protected ContextInterface $context;
    /** @psalm-readonly string */
    protected string $type;

    /** @psalm-var array<string, true> */
    protected array $hints = [];
    /** @psalm-var Representation[] */
    protected array $representations = [];

    public function __construct(ContextInterface $context, string $type)
    {
        $this->context = $context;
        $this->type = $type;
    }

    public function __clone()
    {
        $this->context = clone $this->context;
    }

    public function getContext(): ContextInterface
    {
        return $this->context;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHints(): array
    {
        return $this->hints;
    }

    public function hasHint(string $hint): bool
    {
        return isset($this->hints[$hint]);
    }

    public function addHint(string $hint, ?int $pos = null): void
    {
        if (null === $pos) {
            $this->hints[$hint] = true;
        } else {
            unset($this->hints[$hint]);
            $this->hints = \array_merge(
                \array_slice($this->hints, 0, $pos),
                [$hint => true],
                \array_slice($this->hints, $pos)
            );
        }
    }

    public function removeHint(string $hint): void
    {
        unset($this->hints[$hint]);
    }

    /** @psalm-param array<string, mixed> $hints */
    public function appendHints(array $hints): void
    {
        foreach ($hints as $hint => $_) {
            $this->addHint($hint);
        }
    }

    public function clearHints(): void
    {
        $this->hints = [];
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

    /** @psalm-param Representation[] $reps */
    public function appendRepresentations(array $reps): void
    {
        foreach ($reps as $rep) {
            $this->addRepresentation($rep);
        }
    }

    public function clearRepresentations(): void
    {
        $this->representations = [];
    }

    public function getDisplayType(): string
    {
        return $this->type;
    }

    public function getDisplayName(): string
    {
        return (string) $this->context->getName();
    }

    public function getDisplaySize(): ?string
    {
        return null;
    }

    public function getDisplayValue(): ?string
    {
        return null;
    }

    /** @psalm-return AbstractValue[] */
    public function getDisplayChildren(): array
    {
        return [];
    }
}
