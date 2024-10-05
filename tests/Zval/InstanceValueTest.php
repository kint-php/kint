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

namespace Kint\Test\Zval;

use Kint\Parser\Parser;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\KintTestCase;
use Kint\Zval\Context\BaseContext;
use Kint\Zval\InstanceValue;

/**
 * @coversNothing
 */
class InstanceValueTest extends KintTestCase
{
    /**
     * @covers \Kint\Zval\InstanceValue::__construct
     */
    public function testConstruct()
    {
        $c = new BaseContext('name');
        $v = new InstanceValue($c, 'classname', 'hash', 1234);

        $this->assertSame($c, $v->getContext());
        $this->assertSame('classname', $v->classname);
        $this->assertSame('hash', $v->spl_object_hash);
        $this->assertSame(1234, $v->spl_object_id);
    }

    /**
     * @covers \Kint\Zval\InstanceValue::transplant
     */
    public function testTransplant()
    {
        $o = new InstanceValue(new BaseContext('myname'), 'myclass', 'myhash', 1234);
        $o->filename = 'myfile';
        $o->startline = 1234;
        $o->size = 42;

        $o2 = new InstanceValue(new BaseContext('notmyname'), 'notmyclass', 'notmyhash', -1234);

        $this->assertNotEquals($o->getContext(), $o2->getContext());
        $this->assertNotEquals($o->classname, $o2->classname);
        $this->assertNotEquals($o->spl_object_hash, $o2->spl_object_hash);
        $this->assertNotEquals($o->spl_object_id, $o2->spl_object_id);
        $this->assertNotEquals($o->filename, $o2->filename);
        $this->assertNotEquals($o->startline, $o2->startline);
        $this->assertNotEquals($o->size, $o2->size);

        $o2->transplant($o);

        $this->assertNotEquals($o->getContext(), $o2->getContext());
        $this->assertNotEquals($o->classname, $o2->classname);
        $this->assertNotEquals($o->spl_object_hash, $o2->spl_object_hash);
        $this->assertNotEquals($o->spl_object_id, $o2->spl_object_id);
        $this->assertSame($o->filename, $o2->filename);
        $this->assertSame($o->startline, $o2->startline);
        $this->assertSame($o->size, $o2->size);
    }

    /**
     * @covers \Kint\Zval\InstanceValue::getType
     */
    public function testGetType()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertSame(ChildTestClass::class, $o->getType());
    }
}
