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
use Kint\Value\Context\PropertyContext;

/**
 * @coversNothing
 */
class PropertyContextTest extends KintTestCase
{
    public function modifierProvider()
    {
        $pub_ro = new PropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $pub_ro->readonly = true;
        $pri_ro = new PropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PRIVATE);
        $pri_ro->readonly = true;

        $pub_pro = new PropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $pub_pro->access_set = ClassDeclaredContext::ACCESS_PROTECTED;
        $pub_pri = new PropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $pub_pri->access_set = ClassDeclaredContext::ACCESS_PRIVATE;
        $pro_pri = new PropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PROTECTED);
        $pro_pri->access_set = ClassDeclaredContext::ACCESS_PRIVATE;

        return [
            'public' => [
                new PropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC),
                'public',
            ],
            'protected' => [
                new PropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PROTECTED),
                'protected',
            ],
            'private' => [
                new PropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PRIVATE),
                'private',
            ],
            'public readonly' => [
                $pub_ro,
                'public readonly',
            ],
            'private readonly' => [
                $pri_ro,
                'private readonly',
            ],
            'private(set) set hook' => [
                $pub_pro,
                'protected(set)',
            ],
            'protected(set) set hook' => [
                $pub_pri,
                'private(set)',
            ],
            'protected private(set) set hook' => [
                $pro_pri,
                'protected private(set)',
            ],
        ];
    }

    /**
     * @dataProvider modifierProvider
     *
     * @covers \Kint\Value\Context\PropertyContext::getModifiers
     */
    public function testGetModifiers(PropertyContext $c, string $expect)
    {
        $this->assertSame($expect, $c->getModifiers());
    }

    /**
     * @covers \Kint\Value\Context\PropertyContext::getHooks
     */
    public function testGetHooks()
    {
        $c = new PropertyContext('base', 'class', ClassDeclaredContext::ACCESS_PUBLIC);
        $c->hook_set_type = 'KintTestTypeGoesHere';
        $this->assertNull($c->getHooks());

        $c->hooks = PropertyContext::HOOK_GET;
        $this->assertSame('{ get; }', $c->getHooks());
        $c->hooks = PropertyContext::HOOK_GET | PropertyContext::HOOK_GET_REF;
        $this->assertSame('{ &get; }', $c->getHooks());
        $c->hooks = PropertyContext::HOOK_SET;
        $this->assertSame('{ set; }', $c->getHooks());
        $c->hooks = PropertyContext::HOOK_SET | PropertyContext::HOOK_GET;
        $this->assertSame('{ get; set; }', $c->getHooks());
        $c->hooks = PropertyContext::HOOK_SET | PropertyContext::HOOK_GET | PropertyContext::HOOK_GET_REF;
        $this->assertSame('{ &get; set; }', $c->getHooks());

        $c->hooks = PropertyContext::HOOK_SET | PropertyContext::HOOK_SET_TYPE;
        $this->assertSame('{ set(KintTestTypeGoesHere); }', $c->getHooks());
        $c->hook_set_type = null;
        $this->assertSame('{ set; }', $c->getHooks());
    }
}
