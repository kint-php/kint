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

use Kint\Zval\Context\BaseContext;
use Kint\Zval\Representation\CallableDefinitionRepresentation;
use ReflectionFunction;

class FunctionValue extends Value
{
    public ?string $type = 'function';
    /** @psalm-var array<string, true> */
    public array $hints = [
        'callable' => true,
        'function' => true,
    ];
    public DeclaredCallableBag $callable_bag;

    public function __construct(ReflectionFunction $method)
    {
        parent::__construct(new BaseContext($method->getName()));
        $this->callable_bag = new DeclaredCallableBag($method);

        if ($this->callable_bag->internal) {
            return;
        }

        /**
         * @psalm-var string $this->callable_bag->filename
         * @psalm-var int $this->callable_bag->startline
         * Psalm issue #11121
         */
        $docstring = new CallableDefinitionRepresentation(
            $this->callable_bag->filename,
            $this->callable_bag->startline,
            null,
            $this->callable_bag->docstring
        );

        $docstring->implicit_label = true;
        $this->addRepresentation($docstring);
        $this->value = $docstring;
    }

    public function getValueShort(): ?string
    {
        if ($this->value instanceof CallableDefinitionRepresentation) {
            return $this->value->getDocstringOneLine();
        }

        return parent::getValueShort();
    }

    public function getPhpDocUrl(): ?string
    {
        if (!$this->callable_bag->internal) {
            return null;
        }

        return 'https://www.php.net/function.'.\str_replace('_', '-', \strtolower((string) $this->context->getName()));
    }

    public function getDisplayName(): string
    {
        return $this->context->getName().'('.$this->callable_bag->getParams().')';
    }
}
