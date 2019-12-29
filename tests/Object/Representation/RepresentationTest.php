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

namespace Kint\Test\Object\Representation;

use Kint\Object\Representation\Representation;
use Kint\Test\KintTestCase;

class RepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Object\Representation\Representation::__construct
     */
    public function testConstruct()
    {
        $r = new Representation('This is a label');
        $this->assertSame('This is a label', $r->label);
        $this->assertSame('this_is_a_label', $r->getName());

        $r = new Representation('This is a label', 'this_is_a_name');
        $this->assertSame('This is a label', $r->label);
        $this->assertSame('this_is_a_name', $r->getName());

        $r = new Representation('Test # 3');
        $this->assertSame('Test # 3', $r->label);
        $this->assertSame('test_3', $r->getName());
    }

    /**
     * @covers \Kint\Object\Representation\Representation::getLabel
     */
    public function testGetLabel()
    {
        $r = new Representation('This is a label');
        $this->assertSame('This is a label', $r->getLabel());
        $r->contents = [1];
        $this->assertSame('This is a label', $r->getLabel());
        $r->contents[] = 2;
        $r->contents[] = 3;
        $this->assertSame('This is a label (3)', $r->getLabel());
    }

    /**
     * @covers \Kint\Object\Representation\Representation::getName
     * @covers \Kint\Object\Representation\Representation::setName
     */
    public function testSetName()
    {
        $r = new Representation('Test');
        $this->assertSame('test', $r->getName());

        $r->setName('Test this string!');
        $this->assertSame('test_this_string_', $r->getName());

        $r->setName('UPPERCASE HOLDS NO POWER HERE');
        $this->assertSame('uppercase_holds_no_power_here', $r->getName());

        $r->setName('Multiple ! # () @!$#%@#$% special characters');
        $this->assertSame('multiple_special_characters', $r->getName());

        $r->setName('But numbers work like 123');
        $this->assertSame('but_numbers_work_like_123', $r->getName());
    }

    /**
     * @covers \Kint\Object\Representation\Representation::labelIsImplicit
     */
    public function testLabelIsImplicit()
    {
        $r = new Representation('This is a label');
        $this->assertFalse($r->labelIsImplicit());
        $r->implicit_label = true;
        $this->assertTrue($r->labelIsImplicit());
    }
}
