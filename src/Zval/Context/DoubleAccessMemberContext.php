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

namespace Kint\Zval\Context;

abstract class DoubleAccessMemberContext extends ClassDeclaredContext
{
    /** @psalm-var ?self::ACCESS_* */
    public ?int $access_set = null;

    protected function getAccess(): string
    {
        switch ($this->access) {
            case self::ACCESS_PUBLIC:
                if (self::ACCESS_PRIVATE === $this->access_set) {
                    return 'private(set)';
                }
                if (self::ACCESS_PROTECTED === $this->access_set) {
                    return 'protected(set)';
                }

                return 'public';

            case self::ACCESS_PROTECTED:
                if (self::ACCESS_PRIVATE === $this->access_set) {
                    return 'protected private(set)';
                }

                return 'protected';

            case self::ACCESS_PRIVATE:
                return 'private';
        }
    }
}