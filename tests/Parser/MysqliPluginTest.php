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

namespace Kint\Test\Parser;

use Kint\Parser\MysqliPlugin;
use Kint\Parser\Parser;
use Kint\Test\Fixtures\MysqliTestClass;
use Kint\Test\KintTestCase;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\PropertyContext;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\StringRepresentation;
use Mysqli;
use stdClass;

/**
 * @coversNothing
 */
class MysqliPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\MysqliPlugin::getTypes
     */
    public function testGetTypes()
    {
        $m = new MysqliPlugin($this->createStub(Parser::class));

        $this->assertContains('object', $m->getTypes());
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::getTriggers
     */
    public function testGetTriggers()
    {
        $m = new MysqliPlugin($this->createStub(Parser::class));

        $this->assertSame(Parser::TRIGGER_COMPLETE, $m->getTriggers());
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parseComplete
     */
    public function testParse()
    {
        $p = new Parser();
        $v = $this->getRealMysqliConnection();
        $base = new BaseContext('$v');
        $base->access_path = '$v';

        $obj1 = $p->parse($v, clone $base);

        $this->assertNotEmpty($obj1->getChildren());
        foreach ($obj1->getChildren() as $obj) {
            $this->assertSame(KINT_PHP81 ? 'uninitialized' : 'null', $obj->getType());
        }

        $m = new MysqliPlugin($p);
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertNotEmpty($obj2->getChildren());
        $this->assertNotEquals($obj1, $obj2);

        foreach ($obj2->getChildren() as $obj) {
            // Values can be actual nulls below 8.1 so we can't check this there
            $this->assertNotSame('uninitialized', $obj->getType());
            $this->assertSame(KINT_PHP81, $obj->getContext()->readonly);
        }
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parseComplete
     */
    public function testBadParse()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = new Mysqli();

        $o = $p->parse($v, clone $b);
        $o->value = null;

        $mp = new MysqliPlugin($p);

        $p->addPlugin($mp);

        $out = $mp->parseComplete($v, $o, Parser::TRIGGER_BEGIN);

        $this->assertSame($out, $o);

        $rep = new StringRepresentation('Contents', 'value');
        $o->addRepresentation($rep);

        $out = $mp->parseComplete($v, $o, Parser::TRIGGER_BEGIN);

        $this->assertSame($out, $o);
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parseComplete
     */
    public function testParseFailedConnection()
    {
        if (KINT_PHP81) {
            $this->markTestSkipped('PHP 8.1 throws exception on failed connection');
        }

        $p = new Parser();
        $v = $this->getRealMysqliFailedConnection();
        $base = new BaseContext('$v');

        $obj1 = $p->parse($v, clone $base);

        $this->assertNotEmpty($obj1->getChildren());
        foreach ($obj1->getChildren() as $obj) {
            $this->assertSame('null', $obj->getType());
        }

        $m = new MysqliPlugin($p);
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertNotEmpty($obj2->getChildren());
        $this->assertNotEquals($obj1, $obj2);
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parseComplete
     */
    public function testParseBadValue()
    {
        $p = new Parser();
        $v = new stdClass();
        $base = new BaseContext('$v');

        $obj1 = $p->parse($v, clone $base);

        $this->assertEmpty($obj1->getChildren());

        $m = new MysqliPlugin($p);
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertEquals($obj1, $obj2);

        $v = new Mysqli();
        $obj1 = new InstanceValue($base, \get_class($v), \spl_object_hash($v), \spl_object_id($v));

        $obj2 = $m->parseComplete($v, $obj1, Parser::TRIGGER_SUCCESS);

        $this->assertEquals($obj1, $obj2);
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parseComplete
     */
    public function testParseEmptyValue()
    {
        $p = new Parser();
        $v = new Mysqli();
        $base = new BaseContext('$v');

        $obj1 = $p->parse($v, clone $base);

        $this->assertNotEmpty($obj1->getChildren());
        foreach ($obj1->getChildren() as $obj) {
            $this->assertSame(KINT_PHP81 ? 'uninitialized' : 'null', $obj->getType());
        }

        $m = new MysqliPlugin($p);
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertNotEmpty($obj2->getChildren());
        $this->assertNotEquals($obj1, $obj2);

        foreach ($obj2->getChildren() as $obj) {
            $objname = $obj->getContext()->getName();
            if (isset(MysqliPlugin::EMPTY_READABLE[$objname]) || isset(MysqliPlugin::ALWAYS_READABLE[$objname])) {
                $this->assertNotSame('uninitialized', $obj->getType());
                $this->assertSame(KINT_PHP81, $obj->getContext()->readonly);
            } else {
                $this->assertSame(KINT_PHP81 ? 'uninitialized' : 'null', $obj->getType());
            }
        }
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parseComplete
     */
    public function testParseExtraParams()
    {
        $p = new Parser();
        $base = new BaseContext('$v');

        @$v = new MysqliTestClass(\getenv('MYSQLI_HOST'), \getenv('MYSQLI_USER'), \getenv('MYSQLI_PASS'));

        if ($v->connect_errno) {
            $this->markTestSkipped('Mysqli connection error. Check connection information in phpunit.xml');
        }

        $v->testvar = 'Hello world';

        $obj1 = $p->parse($v, clone $base);

        $basecount = \count(MysqliPlugin::ALWAYS_READABLE) + \count(MysqliPlugin::EMPTY_READABLE) + \count(MysqliPlugin::CONNECTED_READABLE);

        $this->assertCount($basecount + 1, $obj1->getChildren());
        foreach ($obj1->getChildren() as $obj) {
            switch ($obj->getContext()->getName()) {
                case 'affected_rows':
                    $this->assertSame('integer', $obj->getType());
                    break;
                case 'testvar':
                    $this->assertSame('string', $obj->getType());
                    break;
                default:
                    $this->assertSame(KINT_PHP81 ? 'uninitialized' : 'null', $obj->getType());
                    break;
            }
        }

        $m = new MysqliPlugin($p);
        $p->addPlugin($m);

        $obj2 = $p->parse($v, clone $base);

        $this->assertNotEmpty($obj2->getChildren());
        $this->assertNotEquals($obj1, $obj2);

        foreach ($obj2->getChildren() as $obj) {
            $this->assertNotSame('uninitialized', $obj->getType());
            if ('testvar' === $obj->getContext()->getName()) {
                $this->assertNotInstanceOf(PropertyContext::class, \get_class($obj->getContext()));
            } else {
                $this->assertInstanceOf(PropertyContext::class, $obj->getContext());
                $this->assertSame(KINT_PHP81, $obj->getContext()->readonly);
            }
        }
    }

    /**
     * @covers \Kint\Parser\MysqliPlugin::parseComplete
     */
    public function testParseMultipleConnections()
    {
        $p = new Parser();
        $base = new BaseContext('$v');

        $m = new MysqliPlugin($p);
        $p->addPlugin($m);

        $v_empty = new Mysqli();
        $obj_empty = $p->parse($v_empty, clone $base);

        if (!KINT_PHP81) {
            $v_bad = $this->getRealMysqliFailedConnection();
            $obj_bad = $p->parse($v_bad, clone $base);
            $obj_empty_after_bad = $p->parse($v_empty, clone $base);
        }

        $v_good = $this->getRealMysqliConnection();
        $obj_good = $p->parse($v_good, clone $base);
        $obj_empty_after_good = $p->parse($v_empty, clone $base);

        // Compare some stuff
        $obj_comparisons = [$obj_empty, $obj_empty_after_good];
        if (!KINT_PHP81) {
            $obj_comparisons[] = $obj_empty_after_bad;
        }
        foreach ($obj_comparisons as $obj) {
            $this->assertNotEmpty($obj->getChildren());
            $found = 0;
            foreach ($obj->getChildren() as $child) {
                switch ($child->getContext()->getName()) {
                    case 'affected_rows':
                        $this->assertSame(KINT_PHP81 ? 'uninitialized' : 'null', $child->getType());
                        $found |= 1;
                        break;
                    case 'client_info':
                        $this->assertSame('string', $child->getType());
                        $found |= 2;
                        break;
                    case 'client_version':
                        $this->assertSame('integer', $child->getType());
                        $found |= 4;
                        break;
                }
            }
            $this->assertSame(1 | 2 | 4, $found);
        }

        if (!KINT_PHP81) {
            $this->assertNotEmpty($obj_bad->getChildren());
            $found = 0;
            foreach ($obj_bad->getChildren() as $child) {
                switch ($child->getContext()->getName()) {
                    case 'affected_rows':
                        $this->assertSame(KINT_PHP81 ? 'uninitialized' : 'null', $child->getType());
                        $found |= 1;
                        break;
                    case 'client_info':
                        $this->assertSame(KINT_PHP81 ? 'uninitialized' : 'null', $child->getType());
                        $found |= 2;
                        break;
                    case 'client_version':
                        $this->assertSame('integer', $child->getType());
                        $found |= 4;
                        break;
                }
            }
            $this->assertSame(1 | 2 | 4, $found);
        }

        $this->assertNotEmpty($obj_good->getChildren());
        $found = 0;
        foreach ($obj_good->getChildren() as $child) {
            switch ($child->getContext()->getName()) {
                case 'affected_rows':
                    $this->assertSame('integer', $child->getType());
                    $found |= 1;
                    break;
                case 'client_info':
                    $this->assertSame('string', $child->getType());
                    $found |= 2;
                    break;
                case 'client_version':
                    $this->assertSame('integer', $child->getType());
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
