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

namespace Kint\Test\Parser;

use Kint\Object\BasicObject;
use Kint\Parser\MysqliPlugin;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\MysqliTestClass;
use Kint\Test\KintTestCase;
use Mysqli;
use stdClass;

class MysqliPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\MysqliPlugin::getTypes
     */
    public function testGetTypes()
    {
        $m = new MysqliPlugin();

        $this->assertContains('object', $m->getTypes());
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::getTriggers
     */
    public function testGetTriggers()
    {
        $m = new MysqliPlugin();

        $this->assertSame(Parser::TRIGGER_COMPLETE, $m->getTriggers());
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parse
     */
    public function testParse()
    {
        $p = new Parser();
        $v = $this->getRealMysqliConnection();
        $base = BasicObject::blank('$v', '$v');

        $obj1 = $p->parse($v, clone $base);

        foreach ($obj1->value->contents as $obj) {
            $this->assertSame('null', $obj->type);
        }

        $m = new MysqliPlugin();
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertNotEquals($obj1, $obj2);
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parse
     */
    public function testParseFailedConnection()
    {
        $p = new Parser();
        $v = $this->getRealMysqliFailedConnection();
        $base = BasicObject::blank('$v', '$v');

        $obj1 = $p->parse($v, clone $base);

        foreach ($obj1->value->contents as $obj) {
            $this->assertSame('null', $obj->type);
        }

        $m = new MysqliPlugin();
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertNotEquals($obj1, $obj2);
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parse
     */
    public function testParseBadObject()
    {
        $p = new Parser();
        $v = new stdClass();
        $base = BasicObject::blank('$v', '$v');

        $obj1 = $p->parse($v, clone $base);

        $this->assertEmpty($obj1->value->contents);

        $m = new MysqliPlugin();
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertEquals($obj1, $obj2);
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parse
     */
    public function testParseEmptyObject()
    {
        $p = new Parser();
        $v = new Mysqli();
        $base = BasicObject::blank('$v', '$v');

        $obj1 = $p->parse($v, clone $base);

        foreach ($obj1->value->contents as $obj) {
            $this->assertSame('null', $obj->type);
        }

        $m = new MysqliPlugin();
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertNotEquals($obj1, $obj2);
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parse
     */
    public function testParseExtraParams()
    {
        $p = new Parser();
        $base = BasicObject::blank('$v', '$v');

        @$v = new MysqliTestClass(\getenv('MYSQLI_HOST'), \getenv('MYSQLI_USER'), \getenv('MYSQLI_PASS'));

        if ($v->connect_errno) {
            $this->markTestSkipped('Mysqli connection error. Check connection information in phpunit.xml');
        }

        $v->testvar = 'Hello world';

        $obj1 = $p->parse($v, clone $base);

        foreach ($obj1->value->contents as $obj) {
            switch ($obj->name) {
                case 'affected_rows':
                    $this->assertSame('integer', $obj->type);
                    break;
                case 'testvar':
                    $this->assertSame('string', $obj->type);
                    break;
                default:
                    $this->assertSame('null', $obj->type);
                    break;
            }
        }

        $m = new MysqliPlugin();
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertNotEquals($obj1, $obj2);
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parse
     */
    public function testParseMultipleConnections()
    {
        $p = new Parser();
        $base = BasicObject::blank('$v', '$v');

        $m = new MysqliPlugin();
        $p->addPlugin($m);

        $v_empty = new Mysqli();
        $obj_empty = $p->parse($v_empty, clone $base);

        $v_bad = $this->getRealMysqliFailedConnection();
        $obj_bad = $p->parse($v_bad, clone $base);
        $obj_empty_after_bad = $p->parse($v_empty, clone $base);

        $v_good = $this->getRealMysqliConnection();
        $obj_good = $p->parse($v_good, clone $base);
        $obj_empty_after_good = $p->parse($v_empty, clone $base);

        // Compare some stuff
        $found = 0;
        foreach ([$obj_empty, $obj_empty_after_bad, $obj_empty_after_good] as $obj) {
            foreach ($obj->value->contents as $child) {
                switch ($child->name) {
                    case 'affected_rows':
                        $this->assertSame('null', $child->type);
                        $found |= 1;
                        break;
                    case 'client_info':
                        $this->assertSame('string', $child->type);
                        $found |= 2;
                        break;
                    case 'client_version':
                        $this->assertSame('integer', $child->type);
                        $found |= 4;
                        break;
                }
            }
        }
        $this->assertSame(1 | 2 | 4, $found);

        $found = 0;
        foreach ($obj_bad->value->contents as $child) {
            switch ($child->name) {
                case 'affected_rows':
                    $this->assertSame('null', $child->type);
                    $found |= 1;
                    break;
                case 'client_info':
                    $this->assertSame('null', $child->type);
                    $found |= 2;
                    break;
                case 'client_version':
                    $this->assertSame('integer', $child->type);
                    $found |= 4;
                    break;
            }
        }
        $this->assertSame(1 | 2 | 4, $found);

        $found = 0;
        foreach ($obj_good->value->contents as $child) {
            switch ($child->name) {
                case 'affected_rows':
                    $this->assertSame('integer', $child->type);
                    $found |= 1;
                    break;
                case 'client_info':
                    $this->assertSame('string', $child->type);
                    $found |= 2;
                    break;
                case 'client_version':
                    $this->assertSame('integer', $child->type);
                    $found |= 4;
                    break;
            }
        }
        $this->assertSame(1 | 2 | 4, $found);
    }

    protected function getRealMysqliConnection()
    {
        @$m = new Mysqli(\getenv('MYSQLI_HOST'), \getenv('MYSQLI_USER'), \getenv('MYSQLI_PASS'));

        if ($m->connect_errno) {
            $this->markTestSkipped('Mysqli connection error. Check connection information in phpunit.xml');
        }

        return $m;
    }

    protected function getRealMysqliFailedConnection()
    {
        @$m = new Mysqli(\getenv('MYSQLI_HOST'), \getenv('MYSQLI_USER'), \getenv('MYSQLI_PASS').'fail');

        $this->assertNotFalse($m->connect_errno);

        return $m;
    }
}
