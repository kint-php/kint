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

use Kint\Utils;
use ReflectionFunctionAbstract;

/**
 * @psalm-readonly
 */
final class DeclaredCallableBag
{
    use ParameterHoldingTrait;

    public bool $internal;
    public ?string $filename;
    public ?int $startline;
    public ?int $endline;
    public ?string $docstring;
    public bool $return_reference;
    public ?string $returntype = null;

    public function __construct(ReflectionFunctionAbstract $callable)
    {
        $this->internal = $callable->isInternal();
        $t = $callable->getFileName();
        $this->filename = false === $t ? null : $t;
        $t = $callable->getStartLine();
        $this->startline = false === $t ? null : $t;
        $t = $callable->getEndLine();
        $this->endline = false === $t ? null : $t;
        $t = $callable->getDocComment();
        $this->docstring = false === $t ? null : $t;
        $this->return_reference = $callable->returnsReference();

        $rt = $callable->getReturnType();
        if ($rt) {
            $this->returntype = Utils::getTypeString($rt);
        }

        foreach ($callable->getParameters() as $param) {
            $this->parameters[] = new ParameterBag($param);
        }
    }
}
