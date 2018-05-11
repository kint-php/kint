<?php

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

namespace Kint\Test;

use PHPUnit_Framework_Constraint;

class ContainsInOrderConstraint extends PHPUnit_Framework_Constraint
{
    protected $expected;

    public function __construct(array $expected)
    {
        parent::__construct();
        $this->expected = $expected;
    }

    public function matches($other)
    {
        $cursor = 0;

        foreach ($this->expected as $substring) {
            $next = \strpos($other, $substring, $cursor);

            if (false === $next) {
                return false;
            }

            $cursor = $next + \strlen($substring) + 1;
        }

        return true;
    }

    public function toString()
    {
        return 'matches array of '.\count($this->expected).' strings';
    }

    protected function failureDescription($other)
    {
        if (\is_string($other)) {
            return 'string '.\strlen($other).' bytes long '.$this->toString();
        }

        return $this->exporter->export($other).' '.$this->toString();
    }
}
