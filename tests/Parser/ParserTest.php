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

use __PHP_Incomplete_Class;
use DomainException;
use Exception;
use InvalidArgumentException;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\Fixtures\Php74ChildTestClass;
use Kint\Test\Fixtures\Php74TestClass;
use Kint\Test\Fixtures\Php81TestClass;
use Kint\Test\Fixtures\Php84TestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Zval\BlobValue;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\ResourceValue;
use Kint\Zval\Value;
use ReflectionObject;
use ReflectionProperty;
use stdClass;

/**
 * @coversNothing
 */
class ParserTest extends KintTestCase
{
    public function testTriggerComplete()
    {
        $this->assertSame(
            Parser::TRIGGER_SUCCESS |
            Parser::TRIGGER_DEPTH_LIMIT |
            Parser::TRIGGER_RECURSION,
            Parser::TRIGGER_COMPLETE
        );
    }

    /**
     * @covers \Kint\Parser\Parser::__construct
     * @covers \Kint\Parser\Parser::getCallerClass
     * @covers \Kint\Parser\Parser::getDepthLimit
     */
    public function testConstruct()
    {
        $marker = new ReflectionProperty(Parser::class, 'marker');

        $marker->setAccessible(true);

        $p1 = new Parser();

        $this->assertSame(0, $p1->getDepthLimit());
        $this->assertNull($p1->getCallerClass());

        $p2 = new Parser(123, 'asdf');

        $this->assertSame(123, $p2->getDepthLimit());
        $this->assertSame('asdf', $p2->getCallerClass());
        $this->assertNotSame($marker->getValue($p1), $marker->getValue($p2));
    }

    /**
     * @covers \Kint\Parser\Parser::setCallerClass
     */
    public function testSetCallerClass()
    {
        $p = new Parser(123, 'abc');
        $this->assertSame('abc', $p->getCallerClass());

        $p->setCallerClass('def');
        $this->assertSame('def', $p->getCallerClass());

        $p->setCallerClass(null);
        $this->assertNull($p->getCallerClass());
    }

    /**
     * @covers \Kint\Parser\Parser::setDepthLimit
     */
    public function testSetDepthLimit()
    {
        $p = new Parser(123, 'abc');
        $this->assertSame(123, $p->getDepthLimit());

        $p->setDepthLimit(456);
        $this->assertSame(456, $p->getDepthLimit());
    }

    /**
     * @covers \Kint\Parser\Parser::noRecurseCall
     */
    public function testNoRecurseCall()
    {
        $p = new Parser();
        $p->setDepthLimit(42);

        $p2 = new Parser();
        $this->assertSame(0, $p2->getDepthLimit());

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_COMPLETE,
            function () use ($p2) {
                $p2->setDepthLimit(43);
            }
        );
        $p->addPlugin($pl);

        $v = 4;
        $o = $p->parse($v, new Value('$v'));

        $this->assertSame(42, $p->getDepthLimit());
        $this->assertSame(43, $p2->getDepthLimit());
    }

    /**
     * @covers \Kint\Parser\Parser::noRecurseCall
     */
    public function testNoRecurseCallWithRecursion()
    {
        $p = new Parser();
        $p->setDepthLimit(42);

        $success = false;

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_COMPLETE,
            function () use ($p, &$success) {
                try {
                    $p->setDepthLimit(43);
                } catch (DomainException $e) {
                    $success = true;
                }
            }
        );
        $p->addPlugin($pl);

        $v = 4;
        $o = $p->parse($v, new Value('$v'));

        $this->assertTrue($success, 'Failed to throw domain exception on recursed call');
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseGeneric
     */
    public function testParseInteger()
    {
        $p = new Parser();
        $b = new Value('$v');
        $b->access_path = '$v';
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $this->assertSame('$v', $o->access_path);
        $this->assertSame('$v', $o->name);
        $this->assertSame('integer', $o->type);
        $this->assertSame(Value::class, \get_class($o));
        $this->assertSame(Representation::class, \get_class($o->value));
        $this->assertSame(1234, $o->value->contents);
        $this->assertSame(1234, $v);
        $this->assertSame(0, $o->depth);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseGeneric
     */
    public function testParseBoolean()
    {
        $p = new Parser();
        $b = new Value('$v');
        $v = true;

        $o = $p->parse($v, clone $b);

        $this->assertSame('boolean', $o->type);
        $this->assertTrue($o->value->contents);

        $v = false;

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseGeneric
     */
    public function testParseDouble()
    {
        $p = new Parser();
        $b = new Value('$v');
        $v = 1234.5678;

        $o = $p->parse($v, clone $b);

        $this->assertSame('double', $o->type);
        $this->assertSame(1234.5678, $o->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseGeneric
     */
    public function testParseNull()
    {
        $p = new Parser();
        $b = new Value('$v');
        $v = null;

        $o = $p->parse($v, clone $b);

        $this->assertSame('null', $o->type);
        $this->assertNull($o->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseString
     */
    public function testParseString()
    {
        $p = new Parser();
        $b = new Value('$v');
        $v = 'The quick brown fox jumps over the lazy dog';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(BlobValue::class, $o);

        $this->assertSame('string', $o->type);
        $this->assertSame($v, $o->value->contents);
        $this->assertTrue($o->value->implicit_label);
        $this->assertSame('ASCII', $o->encoding);
        $this->assertSame(\strlen($v), $o->size);

        // Apologies to Spanish programmers, Google made this sentence.
        $v = 'El zorro marrón rápido salta sobre el perro perezoso';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(BlobValue::class, $o);

        $this->assertSame($v, $o->value->contents);
        $this->assertSame('UTF-8', $o->encoding);
        $this->assertSame(\strlen($v), $o->size);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseResource
     */
    public function testParseResource()
    {
        $p = new Parser();
        $b = new Value('$v');
        $v = \fopen(__FILE__, 'r');

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(ResourceValue::class, $o);

        $this->assertSame('resource', $o->type);
        $this->assertNull($o->value);
        $this->assertSame('stream', $o->resource_type);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseArray
     */
    public function testParseArray()
    {
        $p = new Parser();
        $b = new Value('List');
        $b->access_path = '$v';
        $v = [
            1234,
            'key' => 'value',
            1234 => 5678,
        ];

        $o = $p->parse($v, clone $b);

        $this->assertSame('array', $o->type);
        $this->assertSame('List', $o->name);

        $val = \array_values($o->value->contents);

        $this->assertSame(0, $val[0]->name);
        $this->assertSame(1234, $val[0]->value->contents);
        $this->assertSame('$v[0]', $val[0]->access_path);
        $this->assertSame(Value::OPERATOR_ARRAY, $val[0]->operator);
        $this->assertSame('key', $val[1]->name);
        $this->assertSame('value', $val[1]->value->contents);
        $this->assertSame('$v[\'key\']', $val[1]->access_path);
        $this->assertSame(Value::OPERATOR_ARRAY, $val[1]->operator);
        $this->assertSame(1234, $val[2]->name);
        $this->assertSame(5678, $val[2]->value->contents);
        $this->assertSame('$v[1234]', $val[2]->access_path);
        $this->assertSame(Value::OPERATOR_ARRAY, $val[2]->operator);

        $v = [];

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(Representation::class, $o->value);
        $this->assertCount(0, $o->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::getPropsOrdered
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseObject()
    {
        $p = new Parser(0, TestClass::class);
        $b = new Value('List');
        $b->access_path = '$v';
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(InstanceValue::class, $o);

        $this->assertSame('object', $o->type);
        $this->assertSame('List', $o->name);
        $this->assertSame(ChildTestClass::class, $o->classname);
        $this->assertSame(\spl_object_hash($v), $o->spl_object_hash);
        $this->assertSame(\spl_object_id($v), $o->spl_object_id);

        $props = $o->value->contents;

        $this->assertCount(6, $props);

        if (!KINT_PHP81) {
            $props = [
                $props[4],
                $props[3],
                $props[5],
                $props[0],
                $props[1],
                $props[2],
            ];
        }

        $this->assertSame('pub', $props[0]->name);
        $this->assertSame('array', $props[0]->type);
        $this->assertSame(Value::OPERATOR_OBJECT, $props[0]->operator);
        $this->assertSame(TestClass::class, $props[0]->owner_class);
        $this->assertSame('$v->pub', $props[0]->access_path);
        $this->assertSame('pro', $props[1]->name);
        $this->assertSame('array', $props[1]->type);
        $this->assertSame(ChildTestClass::class, $props[1]->owner_class);
        $this->assertSame(Value::OPERATOR_OBJECT, $props[1]->operator);
        $this->assertSame('$v->pro', $props[1]->access_path);
        $this->assertSame('pri', $props[2]->name);
        $this->assertSame('array', $props[2]->type);
        $this->assertSame(Value::OPERATOR_OBJECT, $props[2]->operator);
        $this->assertSame(TestClass::class, $props[2]->owner_class);
        $this->assertSame('$v->pri', $props[2]->access_path);

        $this->assertSame('pub2', $props[3]->name);
        $this->assertSame('null', $props[3]->type);
        $this->assertSame(Value::OPERATOR_OBJECT, $props[3]->operator);
        $this->assertSame(ChildTestClass::class, $props[3]->owner_class);
        $this->assertSame('$v->pub2', $props[3]->access_path);
        $this->assertSame('pro2', $props[4]->name);
        $this->assertSame('null', $props[4]->type);
        $this->assertSame(Value::OPERATOR_OBJECT, $props[4]->operator);
        $this->assertSame(ChildTestClass::class, $props[4]->owner_class);
        $this->assertSame('$v->pro2', $props[4]->access_path);
        $this->assertSame('pri2', $props[5]->name);
        $this->assertSame('null', $props[5]->type);
        $this->assertSame(Value::OPERATOR_OBJECT, $props[5]->operator);
        $this->assertSame(ChildTestClass::class, $props[5]->owner_class);
        $this->assertNull($props[5]->access_path);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseObjectUninitialized()
    {
        $p = new Parser();
        $b = new Value('Object');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $pluginCount = 0;

        $pl = new ProxyPlugin(
            ['uninitialized', 'integer', 'string', 'null'],
            Parser::TRIGGER_SUCCESS | Parser::TRIGGER_BEGIN,
            function (&$var, &$o) use (&$pluginCount) {
                ++$pluginCount;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $val = \array_values($o->value->contents);

        $expected = [
            ['a', 'integer', '$v->a', 'a'],
            ['b', 'string', '$v->b', 'b'],
            'c' => ['c', 'string', '$v->c', 'c'],
            ['d', 'null', '$v->d', 'd'],
            ['e', 'null', '$v->e', 'e'],
            ['f', 'null', '$v->f', 'f'],
            ['g', 'uninitialized', '$v->g', 'g'],
            ['prot_a', 'integer', false, "\0*\0prot_a"],
            ['prot_b', 'string', false, "\0*\0prot_b"],
            ['prot_c', 'uninitialized', false, "\0*\0prot_c"],
            ['prot_d', 'null', false, "\0*\0prot_d"],
            ['prot_e', 'null', false, "\0*\0prot_e"],
            ['prot_f', 'null', false, "\0*\0prot_f"],
            ['prot_g', 'uninitialized', false, "\0*\0prot_g"],
            ['priv_a', 'integer', false, "\0".Php74TestClass::class."\0priv_a"],
            ['priv_b', 'string', false, "\0".Php74TestClass::class."\0priv_b"],
            ['priv_c', 'uninitialized', false, "\0".Php74TestClass::class."\0priv_c"],
            ['priv_d', 'null', false, "\0".Php74TestClass::class."\0priv_d"],
            ['priv_e', 'null', false, "\0".Php74TestClass::class."\0priv_e"],
            ['priv_f', 'null', false, "\0".Php74TestClass::class."\0priv_f"],
            ['priv_g', 'uninitialized', false, "\0".Php74TestClass::class."\0priv_g"],
            'last' => ['last', 'string', false, "\0".Php74ChildTestClass::class."\0last"],
        ];

        $this->assertSame(\count($expected) * 2, $pluginCount);

        // Different order before 8.1
        if (KINT_PHP81) {
            $expected = \array_values($expected);
        } else {
            $last = $expected['last'];
            $c = $expected['c'];
            unset($expected['c'], $expected['last']);
            \array_unshift($expected, $c, $last);
        }

        $arraycast = (array) $v;

        foreach ($expected as $index => $expect) {
            $this->assertSame($expect[0], $val[$index]->name);
            $this->assertSame($expect[1], $val[$index]->type);

            if ($expect[2]) {
                $this->assertSame($expect[2], $val[$index]->access_path);
            } else {
                $this->assertNull($val[$index]->access_path);
            }

            if ('uninitialized' !== $expect[1]) {
                \reset($arraycast);
                $this->assertSame(\key($arraycast), $expect[3]);
                $this->assertSame(\strtolower(\gettype(\reset($arraycast))), $expect[1]);
                \array_shift($arraycast);
            }
        }
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     * @covers \Kint\Parser\Parser::childHasPath
     */
    public function testParseObjectIncomplete()
    {
        $p = new Parser();
        $b = new Value('List');
        $b->access_path = '$v';
        $v = \unserialize('O:1:"a":1:{s:1:"b";s:4:"test";}');

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(InstanceValue::class, $o);

        $this->assertSame('object', $o->type);
        $this->assertSame('List', $o->name);
        $this->assertSame(__PHP_Incomplete_Class::class, $o->classname);
        $this->assertSame(\spl_object_hash($v), $o->spl_object_hash);
        $this->assertNotNull($o->access_path);
        $this->assertSame(\spl_object_id($v), $o->spl_object_id);

        $val = \array_values($o->value->contents);

        $this->assertSame('__PHP_Incomplete_Class_Name', $val[0]->name);
        $this->assertSame('string', $val[0]->type);
        $this->assertSame(Value::OPERATOR_OBJECT, $val[0]->operator);
        $this->assertNull($val[0]->access_path);
        $this->assertSame('a', $val[0]->value->contents);

        $this->assertSame('b', $val[1]->name);
        $this->assertSame('string', $val[1]->type);
        $this->assertSame(Value::OPERATOR_OBJECT, $val[1]->operator);
        $this->assertNull($val[1]->access_path);
        $this->assertSame('test', $val[1]->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseObjectReadonly()
    {
        if (!KINT_PHP81) {
            $this->markTestSkipped('Not testing readonly properties below PHP 8.1');
        }

        $p = new Parser();
        $b = new Value('Object');
        $b->access_path = '$v';
        $v = new Php81TestClass('test string');

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(InstanceValue::class, $o);

        $val = \array_values($o->value->contents);

        $this->assertSame('a', $val[0]->name);
        $this->assertTrue($val[0]->readonly);
        $this->assertSame('b', $val[1]->name);
        $this->assertTrue($val[1]->readonly);
        $this->assertSame('c', $val[2]->name);
        $this->assertTrue($val[2]->readonly);
        $this->assertSame('d', $val[3]->name);
        $this->assertTrue($val[3]->readonly);

        // $v->d[0] === $v->a
        $this->assertSame($val[3]->value->contents[0]->value->contents, $val[0]->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseResourceClosed
     */
    public function testParseUnknown()
    {
        $p = new Parser();
        $b = new Value('$v');
        $v = \fopen(__FILE__, 'r');
        \fclose($v);

        $o = $p->parse($v, clone $b);

        $this->assertSame('resource (closed)', $o->type);
        $this->assertNull($o->value);
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseReferences()
    {
        $p = new Parser();
        $b = new Value('$v');
        $r = 1234;
        $v = [&$r, 1234, new stdClass()];

        $o = $p->parse($v, clone $b);

        $this->assertTrue($o->value->contents[0]->reference);
        $this->assertFalse($o->value->contents[1]->reference);
        $this->assertFalse($o->value->contents[2]->reference);

        $v = new stdClass();
        $v->v1 = &$r;
        $v->v2 = 1234;
        $v->v3 = new stdClass();

        $o = $p->parse($v, clone $b);

        $this->assertTrue($o->value->contents[0]->reference);
        $this->assertFalse($o->value->contents[1]->reference);
        $this->assertFalse($o->value->contents[2]->reference);

        $propval = 'test';
        $v = new Php74TestClass();
        $v->b = &$propval;

        $o = $p->parse($v, clone $b);

        foreach ($o->value->contents as $val) {
            $this->assertSame('b' === $val->name, $val->reference);
        }

        $v = new Php74TestClass();
        $v->b = 'test';
        $a = [
            'testval' => $v->b,
            'testref' => &$v->b,
        ];

        $o = $p->parse($a, clone $b);

        foreach ($o->value->contents as $val) {
            $this->assertSame('testref' === $val->name, $val->reference);
        }
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseRecursion()
    {
        $p = new Parser();
        $b = new Value('$v');
        $v = [];
        $v[] = &$v;

        $recursed = false;

        $pl = new ProxyPlugin(
            ['array', 'object'],
            Parser::TRIGGER_RECURSION,
            function () use (&$recursed) {
                $recursed = true;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertArrayHasKey('recursion', $o->value->contents[0]->hints);
        $this->assertTrue($recursed);

        $v = new stdClass();
        $v->v = $v;

        $recursed = false;

        $o = $p->parse($v, clone $b);

        $this->assertArrayHasKey('recursion', $o->value->contents[0]->hints);
        $this->assertTrue($recursed);
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseDepthLimit()
    {
        $p = new Parser(1);
        $b = new Value('$v');
        $v = [[1234]];

        $limit = false;

        $pl = new ProxyPlugin(
            ['array', 'object'],
            Parser::TRIGGER_DEPTH_LIMIT,
            function () use (&$limit) {
                $limit = true;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertArrayHasKey('depth_limit', $o->value->contents[0]->hints);
        $this->assertTrue($limit);

        $limit = false;

        $v = new stdClass();
        $v->v = 1234;
        $v = [$v];

        $o = $p->parse($v, clone $b);

        $this->assertArrayHasKey('depth_limit', $o->value->contents[0]->hints);
        $this->assertTrue($limit);
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseCastKeys()
    {
        $p = new Parser();
        $b = new Value('$v');
        $b->access_path = '$v';

        // Object from array
        $v1 = (object) ['value'];
        $o1 = $p->parse($v1, clone $b);

        // Normal object
        $v2 = new stdClass();
        $v2->{0} = 'value';
        $o2 = $p->parse($v2, clone $b);

        // Array from object
        $v3 = new stdClass();
        $v3->{0} = 'value';
        $v3 = (array) $v3;
        $o3 = $p->parse($v3, clone $b);

        // Normal array
        $v4 = ['value'];
        $o4 = $p->parse($v4, clone $b);

        // Object with both
        $v5 = (object) ['value'];
        $v5->{0} = 'value2';
        $o5 = $p->parse($v5, clone $b);

        // Array with both
        $v6 = new stdClass();
        $v6->{0} = 'value';
        $v6 = (array) $v6;
        $v6['0'] = 'value2';
        $o6 = $p->parse($v6, clone $b);

        // Object from array
        $this->assertSame(1, $o1->size);
        $this->assertSame('value', $o1->value->contents[0]->value->contents);
        $this->assertSame('$v->{\'0\'}', $o1->value->contents[0]->access_path);
        $this->assertTrue(isset($v1->{'0'}));
        $this->assertSame('0', $o1->value->contents[0]->name);

        // Normal object
        $this->assertSame(1, $o2->size);
        $this->assertSame('value', $o2->value->contents[0]->value->contents);
        $this->assertSame('$v->{\'0\'}', $o2->value->contents[0]->access_path);
        $this->assertTrue(isset($v2->{'0'}));
        $this->assertSame('0', $o2->value->contents[0]->name);

        // Array from object
        $this->assertSame(1, $o3->size);
        $this->assertSame('value', $o3->value->contents[0]->value->contents);
        $this->assertSame('$v[0]', $o3->value->contents[0]->access_path);
        $this->assertTrue(isset($v3['0']));
        $this->assertSame(0, $o3->value->contents[0]->name);

        // Normal array
        $this->assertSame(1, $o4->size);
        $this->assertSame('value', $o4->value->contents[0]->value->contents);
        $this->assertSame('$v[0]', $o4->value->contents[0]->access_path);
        $this->assertTrue(isset($v4['0']));
        $this->assertSame(0, $o4->value->contents[0]->name);

        // Object with both
        $this->assertSame(1, $o5->size);
        $this->assertSame('value2', $o5->value->contents[0]->value->contents);
        $this->assertSame('$v->{\'0\'}', $o5->value->contents[0]->access_path);
        $this->assertSame('0', $o5->value->contents[0]->name);

        // Array with both
        $this->assertSame(1, $o6->size);
        $this->assertSame('value2', $o6->value->contents[0]->value->contents);
        $this->assertSame('$v[0]', $o6->value->contents[0]->access_path);
        $this->assertSame(0, $o6->value->contents[0]->name);

        // Object with both and weak equality (As of PHP 7.2)
        $v7 = (object) ['value'];
        $v7->{'0'} = 'value2';
        $v7->{''} = 'value3';
        $o7 = $p->parse($v7, clone $b);

        // Object with both and weak equality
        $this->assertSame(2, $o7->size);
        foreach ($o7->value->contents as $o) {
            $this->assertContains($o->value->contents, ['value2', 'value3']);

            if ('value2' === $o->value->contents) {
                $this->assertSame('$v->{\'0\'}', $o->access_path);
                $this->assertSame('0', $o->name);
            } elseif ('value3' === $o->value->contents) {
                $this->assertSame('$v->{\'\'}', $o->access_path);
                $this->assertSame('', $o->name);
            }
        }
    }

    /**
     * @covers \Kint\Parser\Parser::childHasPath
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseAccessPathAvailability()
    {
        $b = new Value('$v');
        $b->access_path = '$v';
        $v = new ChildTestClass();

        $p = new Parser();
        $o = $p->parse($v, clone $b);
        $properties = [];
        foreach ($o->value->contents as $prop) {
            $properties[$prop->name] = $prop;
        }
        $this->assertSame('$v->pub', $properties['pub']->access_path);
        $this->assertNull($properties['pro']->access_path);
        $this->assertNull($properties['pri']->access_path);

        $p = new Parser(0, ChildTestClass::class);
        $o = $p->parse($v, clone $b);
        $properties = [];
        foreach ($o->value->contents as $prop) {
            $properties[$prop->name] = $prop;
        }
        $this->assertSame('$v->pub', $properties['pub']->access_path);
        $this->assertSame('$v->pro', $properties['pro']->access_path);
        $this->assertNull($properties['pri']->access_path);

        $p = new Parser(0, TestClass::class);
        $o = $p->parse($v, clone $b);
        $properties = [];
        foreach ($o->value->contents as $prop) {
            $properties[$prop->name] = $prop;
        }
        $this->assertSame('$v->pub', $properties['pub']->access_path);
        $this->assertSame('$v->pro', $properties['pro']->access_path);
        $this->assertSame('$v->pri', $properties['pri']->access_path);
        $this->assertSame('$v->pub2', $properties['pub2']->access_path);
        $this->assertSame('$v->pro2', $properties['pro2']->access_path);
        $this->assertNull($properties['pri2']->access_path);
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseHooks()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing hooks below 8.4');
        }

        $b = new Value('$v');
        $b->access_path = '$v';
        $v = new Php84TestClass();

        $p = new Parser();
        $o = $p->parse($v, clone $b);
        $props = $o->value->contents;

        $this->assertSame('a', $props[0]->name);
        $this->assertSame(Value::ACCESS_PUBLIC, $props[0]->access);
        $this->assertFalse($props[0]->virtual);
        $this->assertSame(Value::HOOK_GET | Value::HOOK_SET, $props[0]->hooks);
        $this->assertSame('int', $props[0]->hook_set_type);
        $this->assertSame('uninitialized', $props[0]->type);

        $this->assertSame('b', $props[1]->name);
        $this->assertSame(Value::ACCESS_PROTECTED, $props[1]->access);
        $this->assertFalse($props[1]->virtual);
        $this->assertSame(Value::HOOK_GET, $props[1]->hooks);
        $this->assertNull($props[1]->hook_set_type);
        $this->assertSame('uninitialized', $props[1]->type);

        $this->assertSame('c', $props[2]->name);
        $this->assertSame(Value::ACCESS_PRIVATE, $props[2]->access);
        $this->assertFalse($props[2]->virtual);
        $this->assertSame(Value::HOOK_SET, $props[2]->hooks);
        $this->assertSame('int', $props[2]->hook_set_type);
        $this->assertSame('uninitialized', $props[2]->type);

        $this->assertSame('d', $props[3]->name);
        $this->assertSame(Value::ACCESS_PRIVATE, $props[3]->access);
        $this->assertTrue($props[3]->virtual);
        $this->assertSame(Value::HOOK_GET | Value::HOOK_SET | Value::HOOK_SET_TYPE, $props[3]->hooks);
        $this->assertSame('int|float', $props[3]->hook_set_type);
        $this->assertSame('virtual', $props[3]->type);

        $this->assertSame('e', $props[4]->name);
        $this->assertSame(Value::ACCESS_PROTECTED, $props[4]->access);
        $this->assertTrue($props[4]->virtual);
        $this->assertSame(Value::HOOK_GET | Value::HOOK_GET_REF, $props[4]->hooks);
        $this->assertNull($props[4]->hook_set_type);
        $this->assertSame('virtual', $props[4]->type);

        $this->assertSame('f', $props[5]->name);
        $this->assertSame(Value::ACCESS_PUBLIC, $props[5]->access);
        $this->assertTrue($props[5]->virtual);
        $this->assertSame(Value::HOOK_SET, $props[5]->hooks);
        $this->assertNull($props[5]->hook_set_type);
        $this->assertSame('virtual', $props[5]->type);

        $r = new ReflectionObject($v);
        $v->a = 5;
        $v->f = 10;
        $r->getProperty('c')->setValue($v, 4);

        $o = $p->parse($v, clone $b);
        $props = $o->value->contents;

        $this->assertSame('a', $props[0]->name);
        $this->assertSame(Value::ACCESS_PUBLIC, $props[0]->access);
        $this->assertFalse($props[0]->virtual);
        $this->assertSame(Value::HOOK_GET | Value::HOOK_SET, $props[0]->hooks);
        $this->assertSame('int', $props[0]->hook_set_type);
        $this->assertSame('integer', $props[0]->type);
        $this->assertSame(6, $props[0]->value->contents);

        $this->assertSame('b', $props[1]->name);
        $this->assertSame(Value::ACCESS_PROTECTED, $props[1]->access);
        $this->assertFalse($props[1]->virtual);
        $this->assertSame(Value::HOOK_GET, $props[1]->hooks);
        $this->assertNull($props[1]->hook_set_type);
        $this->assertSame('integer', $props[1]->type);
        $this->assertSame(9, $props[1]->value->contents);

        $this->assertSame('c', $props[2]->name);
        $this->assertSame(Value::ACCESS_PRIVATE, $props[2]->access);
        $this->assertFalse($props[2]->virtual);
        $this->assertSame(Value::HOOK_SET, $props[2]->hooks);
        $this->assertSame('int', $props[2]->hook_set_type);
        $this->assertSame('integer', $props[2]->type);
        $this->assertSame(3, $props[2]->value->contents);

        $this->assertSame('d', $props[3]->name);
        $this->assertSame(Value::ACCESS_PRIVATE, $props[3]->access);
        $this->assertTrue($props[3]->virtual);
        $this->assertSame(Value::HOOK_GET | Value::HOOK_SET | Value::HOOK_SET_TYPE, $props[3]->hooks);
        $this->assertSame('int|float', $props[3]->hook_set_type);
        $this->assertSame('virtual', $props[3]->type);

        $this->assertSame('e', $props[4]->name);
        $this->assertSame(Value::ACCESS_PROTECTED, $props[4]->access);
        $this->assertTrue($props[4]->virtual);
        $this->assertSame(Value::HOOK_GET | Value::HOOK_GET_REF, $props[4]->hooks);
        $this->assertNull($props[4]->hook_set_type);
        $this->assertSame('virtual', $props[4]->type);

        $this->assertSame('f', $props[5]->name);
        $this->assertSame(Value::ACCESS_PUBLIC, $props[5]->access);
        $this->assertTrue($props[5]->virtual);
        $this->assertSame(Value::HOOK_SET, $props[5]->hooks);
        $this->assertNull($props[5]->hook_set_type);
        $this->assertSame('virtual', $props[5]->type);
    }

    /**
     * @covers \Kint\Parser\Parser::addPlugin
     * @covers \Kint\Parser\Parser::applyPlugins
     * @covers \Kint\Parser\Parser::clearPlugins
     */
    public function testPlugins()
    {
        $p = new Parser();
        $b = new Value('$v');
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $this->assertObjectNotHasProperty('testPluginCorrectlyActivated', $o);

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_SUCCESS,
            function (&$var, &$o) {
                $o->hints['testPluginCorrectlyActivated'] = true;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertArrayHasKey('testPluginCorrectlyActivated', $o->hints);

        $p->clearPlugins();

        $o = $p->parse($v, clone $b);

        $this->assertArrayNotHasKey('testPluginCorrectlyActivated', $o->hints);

        $pl = new ProxyPlugin(
            [],
            Parser::TRIGGER_SUCCESS,
            function () {}
        );
        $this->assertFalse($p->addPlugin($pl));

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_NONE,
            function () {}
        );
        $this->assertFalse($p->addPlugin($pl));
    }

    /**
     * @covers \Kint\Parser\Parser::addPlugin
     * @covers \Kint\Parser\Parser::applyPlugins
     */
    public function testTriggers()
    {
        $p = new Parser(1);
        $b = new Value('$v');
        $v = [1234, [1234]];
        $v[] = &$v;

        $triggers = [];

        $pl = new ProxyPlugin(
            ['integer', 'array'],
            Parser::TRIGGER_BEGIN | Parser::TRIGGER_COMPLETE,
            function (&$var, &$o, $trig) use (&$triggers) {
                $triggers[] = $trig;
            }
        );
        $p->addPlugin($pl);

        $p->parse($v, clone $b);

        $this->assertSame(
            [
                Parser::TRIGGER_BEGIN,
                Parser::TRIGGER_BEGIN,
                Parser::TRIGGER_SUCCESS,
                Parser::TRIGGER_BEGIN,
                Parser::TRIGGER_DEPTH_LIMIT,
                Parser::TRIGGER_BEGIN,
                Parser::TRIGGER_RECURSION,
                Parser::TRIGGER_SUCCESS,
            ],
            $triggers
        );
    }

    /**
     * @covers \Kint\Parser\Parser::applyPlugins
     * @covers \Kint\Parser\Parser::haltParse
     * @covers \Kint\Parser\Parser::parse
     */
    public function testHaltParse()
    {
        $p = new Parser();
        $b = new Value('$v');
        $t = clone $b;
        $t->type = 'integer';
        $v = 1234;

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_BEGIN,
            function (&$var, &$o, $trig, $parser) {
                $parser->haltParse();
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, $t);

        $this->assertSame($t, $o);

        $p->clearPlugins();

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_SUCCESS,
            function (&$var, &$o, $trig, $parser) {
                $parser->haltParse();
            }
        );
        $p->addPlugin($pl);

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_SUCCESS,
            function (&$var, &$o) {
                $o->testPluginCorrectlyActivated = true;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertObjectNotHasProperty('testPluginCorrectlyActivated', $o);
    }

    /**
     * @covers \Kint\Parser\Parser::applyPlugins
     */
    public function testPluginExceptionBecomesWarning()
    {
        $p = new Parser();
        $b = new Value('$v');
        $t = clone $b;
        $t->type = 'integer';
        $v = 1234;

        $message = __FUNCTION__;

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_BEGIN,
            function (&$var, &$o, $trig, $parser) use ($message) {
                throw new Exception($message);
            }
        );
        $p->addPlugin($pl);

        $error_triggered = false;

        \set_error_handler(function (int $errno, string $errstr) use (&$error_triggered) {
            $error_triggered = true;
        }, E_WARNING | E_USER_WARNING);

        $p->parse($v, clone $b);

        $this->assertTrue($error_triggered);
    }

    public function childHasPathProvider()
    {
        $data = [];

        $expected = [
            'public parser' => [
                new Parser(),
                [
                    'props' => ['$v', false, false, true, false, false],
                    'statics' => ['$v', true, false, true, false, false],
                    'consts' => ['V', false, true, true, false, false],
                    'props without path' => [null, false, false, false, false, false],
                    'statics without path' => [null, true, false, true, false, false],
                    'consts without path' => [null, false, true, true, false, false],
                ],
            ],
            'protected parser' => [
                new Parser(0, ChildTestClass::class),
                [
                    'props' => ['$v', false, false, true, true, false],
                    'statics' => ['$v', true, false, true, true, false],
                    'consts' => ['V', false, true, true, true, false],
                    'props without path' => [null, false, false, false, false, false],
                    'statics without path' => [null, true, false, true, true, false],
                    'consts without path' => [null, false, true, true, true, false],
                ],
            ],
            'private parser' => [
                new Parser(0, TestClass::class),
                [
                    'props' => ['$v', false, false, true, true, true],
                    'statics' => ['$v', true, false, true, true, true],
                    'consts' => ['V', false, true, true, true, true],
                    'props without path' => [null, false, false, false, false, false],
                    'statics without path' => [null, true, false, true, true, true],
                    'consts without path' => [null, false, true, true, true, true],
                ],
            ],
        ];

        foreach ($expected as $parser_name => $params) {
            [$parser, $opts] = $params;

            foreach ($opts as $name => $set) {
                [$path, $static, $const, $pub, $pro, $pri] = $set;

                $visibilities = [
                    Value::ACCESS_PUBLIC => $pub,
                    Value::ACCESS_PROTECTED => $pro,
                    Value::ACCESS_PRIVATE => $pri,
                ];

                foreach ($visibilities as $visibility => $expect) {
                    $parent = new InstanceValue('parent', ChildTestClass::class, 'objhash', 314159);

                    $r = new Representation('Contents');
                    $parent->addRepresentation($r);

                    $prop = new Value($name);
                    $r->contents = [$prop];
                    $prop->owner_class = TestClass::class;

                    $parent->access_path = $path;
                    $prop->static = $static;
                    $prop->const = $const;
                    $prop->access = $visibility;

                    $data[$parser_name.', '.$visibility.' '.$name] = [$parser, $parent, $prop, $expect];
                }
            }
        }

        return $data;
    }

    /**
     * @dataProvider childHasPathProvider
     *
     * @covers \Kint\Parser\Parser::childHasPath
     *
     * @param Parser $parser
     * @param Value  $parent
     * @param Value  $child
     * @param bool   $expected
     */
    public function testChildHasPath($parser, $parent, $child, $expected)
    {
        $this->assertSame($expected, $parser->childHasPath($parent, $child));
    }

    /**
     * @covers \Kint\Parser\Parser::childHasPath
     */
    public function testInvalidChildHasPath()
    {
        $p = new Parser(0, 'parent');
        $parent = new InstanceValue('parent', 'class', 'hash', 1234);
        $parent->access_path = 'access';
        $child = new Value('child');
        $child->access = Value::ACCESS_PROTECTED;

        $this->expectException(InvalidArgumentException::class);

        $p->childHasPath($parent, $child);
    }

    /**
     * @covers \Kint\Parser\Parser::getCleanArray
     */
    public function testGetCleanArray()
    {
        $p = new Parser();
        $b = new Value('$v');
        $v = [1234];

        $arrays = [];

        $pl = new ProxyPlugin(
            ['array'],
            Parser::TRIGGER_SUCCESS,
            function (&$var, &$o, $trig, $parser) use (&$arrays) {
                $clean = $parser->getCleanArray($var);

                // This here is exactly why you should never alter input
                // variables in plugins and always use getCleanArray
                $var[] = 4321;
                $clean[] = 8765;

                $arrays = [
                    'var' => $var,
                    'clean' => $clean,
                ];
            }
        );
        $p->addPlugin($pl);

        $p->parse($v, clone $b);

        $this->assertSame([1234, 4321], $v);
        $this->assertSame([1234, 8765], $arrays['clean']);
        $this->assertSame(\count($v) + 1, \count($arrays['var']));
    }
}
