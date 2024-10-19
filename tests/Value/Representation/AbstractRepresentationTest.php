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

namespace Kint\Test\Value\Representation;

use Kint\Test\KintTestCase;
use Kint\Value\Representation\AbstractRepresentation;

/**
 * @coversNothing
 */
class AbstractRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Value\Representation\AbstractRepresentation::__construct
     * @covers \Kint\Value\Representation\AbstractRepresentation::getLabel
     * @covers \Kint\Value\Representation\AbstractRepresentation::getName
     */
    public function testConstruct()
    {
        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['This is a label']);
        $this->assertSame('This is a label', $r->getLabel());
        $this->assertSame('this_is_a_label', $r->getName());

        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['This is a label', null]);
        $this->assertSame('This is a label', $r->getLabel());
        $this->assertSame('this_is_a_label', $r->getName());

        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['label', 'this_is_a_name']);
        $this->assertSame('label', $r->getLabel());
        $this->assertSame('this_is_a_name', $r->getName());

        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['Test # 3']);
        $this->assertSame('Test # 3', $r->getLabel());
        $this->assertSame('test_3', $r->getName());
    }

    /**
     * @covers \Kint\Value\Representation\AbstractRepresentation::__construct
     * @covers \Kint\Value\Representation\AbstractRepresentation::getName
     */
    public function testGetName()
    {
        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['Test']);
        $this->assertSame('test', $r->getName());

        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['Test this string!']);
        $this->assertSame('test_this_string_', $r->getName());

        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['UPPERCASE HOLDS NO POWER HERE']);
        $this->assertSame('uppercase_holds_no_power_here', $r->getName());

        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['Multiple ! # () @!$#%@#$% special characters']);
        $this->assertSame('multiple_special_characters', $r->getName());

        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['But numbers work like 123']);
        $this->assertSame('but_numbers_work_like_123', $r->getName());
    }

    /**
     * @covers \Kint\Value\Representation\AbstractRepresentation::__construct
     * @covers \Kint\Value\Representation\AbstractRepresentation::labelIsImplicit
     */
    public function testLabelIsImplicit()
    {
        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['This is a label', null, false]);
        $this->assertFalse($r->labelIsImplicit());
        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['This is a label', null, true]);
        $this->assertTrue($r->labelIsImplicit());
    }

    /**
     * @covers \Kint\Value\Representation\AbstractRepresentation::getHint
     */
    public function testGethint()
    {
        $r = $this->getMockForAbstractClass(AbstractRepresentation::class, ['This is a label']);
        $this->assertNull($r->getHint());
    }
}
