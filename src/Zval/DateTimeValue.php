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

use DateTimeInterface;
use Kint\Zval\Context\ContextInterface;

class DateTimeValue extends InstanceValue
{
    /** @psalm-readonly */
    protected DateTimeInterface $dt;

    /** @psalm-param list<AbstractValue> $properties */
    public function __construct(ContextInterface $context, DateTimeInterface $dt)
    {
        parent::__construct($context, \get_class($dt), \spl_object_hash($dt), \spl_object_id($dt));

        $this->addHint('datetime');
        $this->dt = clone $dt;
    }

    public function getDisplayValue(): string
    {
        $stamp = $this->dt->format('Y-m-d H:i:s');
        if ((int) ($micro = $this->dt->format('u'))) {
            $stamp .= '.'.$micro;
        }
        $stamp .= $this->dt->format(' P');

        $tzn = $this->dt->getTimezone()->getName();
        if ('+' !== $tzn[0] && '-' !== $tzn[0]) {
            $stamp .= $this->dt->format(' T');
        }

        return $stamp;
    }
}
