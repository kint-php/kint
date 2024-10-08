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

class ClosureValue extends InstanceValue
{
    use ParameterHoldingTrait;

    /** @psalm-var array<string, true> */
    public array $hints = [
        'callable' => true,
        'closure' => true,
    ];

    public function getAccessPath(): ?string
    {
        if (null !== $this->access_path) {
            return parent::getAccessPath().'('.$this->getParams().')';
        }

        return null;
    }

    public function getSize(): ?string
    {
        return null;
    }

    public function transplant(Value $old): void
    {
        parent::transplant($old);

        if (0 === $this->depth && null !== $this->access_path && \preg_match('/^\\((function|fn)\\s*\\(/i', $this->access_path, $match)) {
            $this->name = \strtolower($match[1]);
        }
    }
}
