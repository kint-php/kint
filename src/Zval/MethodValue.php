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

use Kint\Zval\Context\ClassDeclaredContext;
use Kint\Zval\Context\MethodContext;
use Kint\Zval\Representation\CallableDefinitionRepresentation;
use ReflectionMethod;

class MethodValue extends Value
{
    public ?string $type = 'method';
    /** @psalm-var array<string, true> */
    public array $hints = [
        'callable' => true,
        'method' => true,
    ];
    public DeclaredCallableBag $callable_bag;

    public function __construct(ReflectionMethod $method)
    {
        $c = new MethodContext(
            $method->getName(),
            $method->getDeclaringClass()->name,
            ClassDeclaredContext::ACCESS_PUBLIC
        );
        $c->static = $method->isStatic();
        $c->abstract = $method->isAbstract();
        $c->final = $method->isFinal();
        if ($method->isProtected()) {
            $c->access = ClassDeclaredContext::ACCESS_PROTECTED;
        } elseif ($method->isPrivate()) {
            $c->access = ClassDeclaredContext::ACCESS_PRIVATE;
        }

        parent::__construct($c);
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
            $c->owner_class,
            $this->callable_bag->docstring
        );

        $docstring->implicit_label = true;
        $this->addRepresentation($docstring);
        $this->value = $docstring;
    }

    public function getContext(): MethodContext
    {
        /**
         * @psalm-var MethodContext $this->context
         * Psalm discuss #11116
         */
        return $this->context;
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

        $class = \str_replace('\\', '-', \strtolower($this->getContext()->owner_class));
        $funcname = \str_replace('_', '-', \strtolower($this->getContext()->getName()));

        if (0 === \strpos($funcname, '--') && 0 !== \strpos($funcname, '-', 2)) {
            $funcname = \substr($funcname, 2);
        }

        return 'https://www.php.net/'.$class.'.'.$funcname;
    }

    public function getDisplayName(): string
    {
        return $this->context->getName().'('.$this->callable_bag->getParams().')';
    }
}
