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

namespace Kint\Test\Value\Context;

use Kint\Test\KintTestCase;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\DoubleAccessMemberContext;

/**
 * @coversNothing
 */
class DoubleAccessMemberContextTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\Context\DoubleAccessMemberContext::getAccess
     */
    public function testGetAccess()
    {
        $c = new class('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC) extends DoubleAccessMemberContext {
            public function getModifiers(): string
            {
                return $this->getAccess();
            }
        };
        $this->assertNull($c->access_set);
        $this->assertSame('public', $c->getModifiers());
        $c->access = ClassDeclaredContext::ACCESS_PROTECTED;
        $this->assertSame('protected', $c->getModifiers());
        $c->access = ClassDeclaredContext::ACCESS_PRIVATE;
        $this->assertSame('private', $c->getModifiers());

        $c->access = ClassDeclaredContext::ACCESS_PUBLIC;
        $c->access_set = ClassDeclaredContext::ACCESS_PUBLIC;
        $this->assertSame('public', $c->getModifiers());
        $c->access_set = ClassDeclaredContext::ACCESS_PROTECTED;
        $this->assertSame('protected(set)', $c->getModifiers());
        $c->access_set = ClassDeclaredContext::ACCESS_PRIVATE;
        $this->assertSame('private(set)', $c->getModifiers());

        $c->access = ClassDeclaredContext::ACCESS_PROTECTED;
        $c->access_set = ClassDeclaredContext::ACCESS_PUBLIC;
        $this->assertSame('protected', $c->getModifiers());
        $c->access_set = ClassDeclaredContext::ACCESS_PROTECTED;
        $this->assertSame('protected', $c->getModifiers());
        $c->access_set = ClassDeclaredContext::ACCESS_PRIVATE;
        $this->assertSame('protected private(set)', $c->getModifiers());

        $c->access = ClassDeclaredContext::ACCESS_PRIVATE;
        $c->access_set = ClassDeclaredContext::ACCESS_PUBLIC;
        $this->assertSame('private', $c->getModifiers());
        $c->access_set = ClassDeclaredContext::ACCESS_PROTECTED;
        $this->assertSame('private', $c->getModifiers());
        $c->access_set = ClassDeclaredContext::ACCESS_PRIVATE;
        $this->assertSame('private', $c->getModifiers());
    }
}
