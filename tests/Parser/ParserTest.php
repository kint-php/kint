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
use DateTime;
use DomainException;
use Exception;
use InvalidArgumentException;
use Kint\Parser\AbstractPlugin;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Test\Fixtures\ChildTestClass;
use Kint\Test\Fixtures\Php74ChildTestClass;
use Kint\Test\Fixtures\Php74TestClass;
use Kint\Test\Fixtures\Php81TestClass;
use Kint\Test\Fixtures\Php84TestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\ClosedResourceValue;
use Kint\Value\Context\ArrayContext;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\ClassOwnedContext;
use Kint\Value\Context\PropertyContext;
use Kint\Value\FixedWidthValue;
use Kint\Value\InstanceValue;
use Kint\Value\Representation\StringRepresentation;
use Kint\Value\ResourceValue;
use Kint\Value\StringValue;
use Kint\Value\UninitializedValue;
use Kint\Value\VirtualValue;
use PHPUnit\Framework\Error\Warning;
use ReflectionObject;
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
        $p1 = new Parser();

        $this->assertSame(0, $p1->getDepthLimit());
        $this->assertNull($p1->getCallerClass());

        $p2 = new Parser(123, 'asdf');

        $this->assertSame(123, $p2->getDepthLimit());
        $this->assertSame('asdf', $p2->getCallerClass());
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
            function (&$var, $v) use ($p2) {
                $p2->setDepthLimit(43);

                return $v;
            }
        );
        $p->addPlugin($pl);

        $v = 4;
        $o = $p->parse($v, new BaseContext('$v'));

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
            function (&$var, $v) use ($p, &$success) {
                try {
                    $p->setDepthLimit(43);
                } catch (DomainException $e) {
                    $this->assertStringStartsWith(Parser::class.'::setDepthLimit ', $e->getMessage());
                    $success = true;
                }

                return $v;
            }
        );
        $p->addPlugin($pl);

        $v = 4;
        $o = $p->parse($v, new BaseContext('$v'));

        $this->assertTrue($success, 'Failed to throw domain exception on recursed call');
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseFixedWidth
     */
    public function testParseInteger()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(FixedWidthValue::class, $o);
        $this->assertSame('$v', $o->getContext()->getAccessPath());
        $this->assertSame('$v', $o->getContext()->getName());
        $this->assertSame('$v', $o->getDisplayName());
        $this->assertSame('integer', $o->getType());
        $this->assertSame(1234, $o->getValue());
        $this->assertSame(0, $o->getContext()->getDepth());
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseFixedWidth
     */
    public function testParseBoolean()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = true;

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(FixedWidthValue::class, $o);
        $this->assertSame('boolean', $o->getType());
        $this->assertTrue($o->getValue());

        $v = false;

        $o = $p->parse($v, clone $b);

        $this->assertFalse($o->getValue());
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseFixedWidth
     */
    public function testParseDouble()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = 1234.5678;

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(FixedWidthValue::class, $o);
        $this->assertSame('double', $o->getType());
        $this->assertSame(1234.5678, $o->getValue());
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseFixedWidth
     */
    public function testParseNull()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = null;

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(FixedWidthValue::class, $o);
        $this->assertSame('null', $o->getType());
        $this->assertNull($o->getValue());
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseString
     */
    public function testParseString()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = 'The quick brown fox jumps over the lazy dog';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(StringValue::class, $o);

        $this->assertSame('string', $o->getType());
        $this->assertSame($v, $o->getValue());
        $this->assertSame('ASCII', $o->getEncoding());
        $this->assertSame(\strlen($v), $o->getLength());

        $this->assertInstanceOf(StringRepresentation::class, $o->getRepresentation('contents'));
        $this->assertTrue($o->getRepresentation('contents')->labelIsImplicit());

        // Apologies to Spanish programmers, Google made this sentence.
        $v = 'El zorro marrón rápido salta sobre el perro perezoso';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(StringValue::class, $o);

        $this->assertSame($v, $o->getValue());
        $this->assertSame('UTF-8', $o->getEncoding());
        $this->assertSame(\strlen($v), $o->getLength());
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseResource
     */
    public function testParseResource()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = \fopen(__FILE__, 'r');

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(ResourceValue::class, $o);

        $this->assertSame('resource', $o->getType());
        $this->assertSame('stream resource', $o->getDisplayType());
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseArray
     */
    public function testParseArray()
    {
        $p = new Parser();
        $b = new BaseContext('List');
        $b->access_path = '$v';
        $v = [
            1234,
            'key' => 'value',
            1234 => 5678,
        ];

        $o = $p->parse($v, clone $b);

        $this->assertSame('array', $o->getType());
        $this->assertSame('List', $o->getContext()->getName());
        $this->assertSame('List', $o->getDisplayName());

        $val = \array_values($o->getContents());

        $this->assertSame(0, $val[0]->getContext()->getName());
        $this->assertSame('0', $val[0]->getDisplayName());
        $this->assertSame(1234, $val[0]->getValue());
        $this->assertInstanceOf(ArrayContext::class, $val[0]->getContext());
        $this->assertSame('$v[0]', $val[0]->getContext()->getAccessPath());
        $this->assertSame('key', $val[1]->getContext()->getName());
        $this->assertSame('key', $val[1]->getDisplayName());
        $this->assertSame('value', $val[1]->getValue());
        $this->assertInstanceOf(ArrayContext::class, $val[1]->getContext());
        $this->assertSame('$v[\'key\']', $val[1]->getContext()->getAccessPath());
        $this->assertSame(1234, $val[2]->getContext()->getName());
        $this->assertSame('1234', $val[2]->getDisplayName());
        $this->assertSame(5678, $val[2]->getValue());
        $this->assertInstanceOf(ArrayContext::class, $val[2]->getContext());
        $this->assertSame('$v[1234]', $val[2]->getContext()->getAccessPath());

        $v = [];

        $o = $p->parse($v, clone $b);

        $this->assertCount(0, $o->getContents());
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::getPropsOrdered
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseObject()
    {
        $p = new Parser(0, TestClass::class);
        $b = new BaseContext('List');
        $b->access_path = '$v';
        $v = new ChildTestClass();
        $v->dynadded = 'value';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(InstanceValue::class, $o);

        $this->assertSame('object', $o->getType());
        $this->assertSame('List', $o->getDisplayName());
        $this->assertSame(ChildTestClass::class, $o->getClassName());
        $this->assertSame(\spl_object_hash($v), $o->getSplObjectHash());
        $this->assertSame(\spl_object_id($v), $o->getSplObjectId());

        $props = $o->getChildren();

        $this->assertCount(7, $props);

        if (!KINT_PHP81) {
            $props = [
                'pub2' => $props[0],
                'pro2' => $props[1],
                'pri2' => $props[2],
                'pro' => $props[3],
                'pub' => $props[4],
                'dynadded' => $props[5],
                'pri' => $props[6],
            ];
        } else {
            $props = [
                'pub' => $props[0],
                'pro' => $props[1],
                'pri' => $props[2],
                'pub2' => $props[3],
                'pro2' => $props[4],
                'pri2' => $props[5],
                'dynadded' => $props[6],
            ];
        }

        $this->assertSame('pub', $props['pub']->getDisplayName());
        $this->assertSame('pub', $props['pub']->getContext()->getName());
        $this->assertSame('array', $props['pub']->getType());
        $this->assertInstanceOf(PropertyContext::class, $props['pub']->getContext());
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props['pub']->getContext()->access);
        $this->assertNull($props['pub']->getContext()->access_set);
        $this->assertSame(TestClass::class, $props['pub']->getContext()->owner_class);
        $this->assertSame('$v->pub', $props['pub']->getContext()->getAccessPath());
        $this->assertSame('pro', $props['pro']->getDisplayName());
        $this->assertSame('array', $props['pro']->getType());
        $this->assertInstanceOf(PropertyContext::class, $props['pro']->getContext());
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $props['pro']->getContext()->access);
        $this->assertNull($props['pro']->getContext()->access_set);
        $this->assertSame(ChildTestClass::class, $props['pro']->getContext()->owner_class);
        $this->assertSame('$v->pro', $props['pro']->getContext()->getAccessPath());
        $this->assertSame('pri', $props['pri']->getDisplayName());
        $this->assertSame('array', $props['pri']->getType());
        $this->assertInstanceOf(PropertyContext::class, $props['pri']->getContext());
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $props['pri']->getContext()->access);
        $this->assertNull($props['pri']->getContext()->access_set);
        $this->assertSame(TestClass::class, $props['pri']->getContext()->owner_class);
        $this->assertSame('$v->pri', $props['pri']->getContext()->getAccessPath());

        $this->assertSame('pub2', $props['pub2']->getDisplayName());
        $this->assertSame('null', $props['pub2']->getType());
        $this->assertInstanceOf(PropertyContext::class, $props['pub2']->getContext());
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props['pub2']->getContext()->access);
        $this->assertNull($props['pub2']->getContext()->access_set);
        $this->assertSame(ChildTestClass::class, $props['pub2']->getContext()->owner_class);
        $this->assertSame('$v->pub2', $props['pub2']->getContext()->getAccessPath());
        $this->assertSame('pro2', $props['pro2']->getDisplayName());
        $this->assertSame('null', $props['pro2']->getType());
        $this->assertInstanceOf(PropertyContext::class, $props['pro2']->getContext());
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $props['pro2']->getContext()->access);
        $this->assertNull($props['pro2']->getContext()->access_set);
        $this->assertSame(ChildTestClass::class, $props['pro2']->getContext()->owner_class);
        $this->assertSame('$v->pro2', $props['pro2']->getContext()->getAccessPath());
        $this->assertSame('pri2', $props['pri2']->getDisplayName());
        $this->assertSame('null', $props['pri2']->getType());
        $this->assertInstanceOf(PropertyContext::class, $props['pri2']->getContext());
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $props['pri2']->getContext()->access);
        $this->assertNull($props['pri2']->getContext()->access_set);
        $this->assertSame(ChildTestClass::class, $props['pri2']->getContext()->owner_class);
        $this->assertNull($props['pri2']->getContext()->getAccessPath());

        $this->assertSame('dynadded', $props['dynadded']->getDisplayName());
        $this->assertSame('string', $props['dynadded']->getType());
        $this->assertInstanceOf(ClassOwnedContext::class, $props['dynadded']->getContext());
        $this->assertSame(ChildTestClass::class, $props['dynadded']->getContext()->owner_class);
        $this->assertSame('$v->dynadded', $props['dynadded']->getContext()->getAccessPath());
        $this->assertSame('value', $props['dynadded']->getValue());
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseObjectUninitialized()
    {
        $p = new Parser();
        $b = new BaseContext('Object');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $o = $p->parse($v, $b);

        $uninit_g = $o->getChildren()[KINT_PHP81 ? 6 : 7];

        $this->assertInstanceOf(UninitializedValue::class, $uninit_g);
        $this->assertSame('uninitialized', $uninit_g->getType());
        $this->assertInstanceOf(PropertyContext::class, $uninit_g->getContext());
        $this->assertSame('g', $uninit_g->getDisplayName());
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseObjectOrder()
    {
        $p = new Parser();
        $b = new BaseContext('Object');
        $b->access_path = '$v';
        $v = new Php74ChildTestClass();

        $pluginCount = 0;

        $pl = new ProxyPlugin(
            ['integer', 'string', 'null'],
            Parser::TRIGGER_SUCCESS | Parser::TRIGGER_BEGIN,
            function (&$var, $o, $trig) use (&$pluginCount) {
                ++$pluginCount;

                return $trig & Parser::TRIGGER_BEGIN ? null : $o;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $val = \array_values($o->getChildren());

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

        $this->assertSame((\count($expected) - 5) * 2, $pluginCount);

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
            $this->assertSame($expect[0], $val[$index]->getContext()->getName());
            $this->assertSame($expect[1], $val[$index]->getType());

            if ($expect[2]) {
                $this->assertSame($expect[2], $val[$index]->getContext()->getAccessPath());
            } else {
                $this->assertNull($val[$index]->getContext()->getAccessPath());
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
     */
    public function testParseObjectIncomplete()
    {
        $p = new Parser();
        $b = new BaseContext('List');
        $b->access_path = '$v';
        $v = \unserialize('O:1:"a":1:{s:1:"b";s:4:"test";}');

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(InstanceValue::class, $o);

        $this->assertSame('object', $o->getType());
        $this->assertSame('List', $o->getDisplayName());
        $this->assertSame(__PHP_Incomplete_Class::class, $o->getClassName());
        $this->assertSame(\spl_object_hash($v), $o->getSplObjectHash());
        $this->assertSame(\spl_object_id($v), $o->getSplObjectId());
        $this->assertNotNull($o->getContext()->getAccessPath());

        $val = \array_values($o->getChildren());

        $this->assertSame('__PHP_Incomplete_Class_Name', $val[0]->getDisplayName());
        $this->assertSame('string', $val[0]->getType());
        $this->assertInstanceOf(ClassOwnedContext::class, $val[0]->getContext());
        $this->assertNull($val[0]->getContext()->getAccessPath());
        $this->assertSame('a', $val[0]->getValue());

        $this->assertSame('b', $val[1]->getDisplayName());
        $this->assertSame('string', $val[1]->getType());
        $this->assertInstanceOf(ClassOwnedContext::class, $val[1]->getContext());
        $this->assertNull($val[1]->getContext()->getAccessPath());
        $this->assertSame('test', $val[1]->getValue());
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
        $b = new BaseContext('Object');
        $b->access_path = '$v';
        $v = new Php81TestClass('test string');

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(InstanceValue::class, $o);

        $val = \array_values($o->getChildren());

        $this->assertSame('a', $val[0]->getDisplayName());
        $this->assertTrue($val[0]->getContext()->readonly);
        $this->assertSame('b', $val[1]->getDisplayName());
        $this->assertTrue($val[1]->getContext()->readonly);
        $this->assertSame('c', $val[2]->getDisplayName());
        $this->assertTrue($val[2]->getContext()->readonly);
        $this->assertSame('d', $val[3]->getDisplayName());
        $this->assertTrue($val[3]->getContext()->readonly);

        // $v->d[0] === $v->a
        $this->assertSame($val[3]->getContents()[0]->getValue(), $val[0]->getValue());
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseObjectNotReliantOnArrayCast()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new DateTime();
        $v->test = 'value';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(InstanceValue::class, $o);

        $val = \array_values($o->getChildren());

        $this->assertCount(1, $val);
        $this->assertSame('test', $val[0]->getDisplayName());
        $this->assertCount(4, (array) $v);
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseAccessPathAvailability()
    {
        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new ChildTestClass();

        $p = new Parser();
        $o = $p->parse($v, clone $b);
        $properties = [];
        foreach ($o->getChildren() as $prop) {
            $properties[$prop->getContext()->getName()] = $prop->getContext();
        }
        $this->assertSame('$v->pub', $properties['pub']->access_path);
        $this->assertNull($properties['pro']->access_path);
        $this->assertNull($properties['pri']->access_path);
        $this->assertSame('$v->pub2', $properties['pub2']->access_path);
        $this->assertNull($properties['pro2']->access_path);
        $this->assertNull($properties['pri2']->access_path);

        $p = new Parser(0, ChildTestClass::class);
        $o = $p->parse($v, clone $b);
        $properties = [];
        foreach ($o->getChildren() as $prop) {
            $properties[$prop->getContext()->getName()] = $prop->getContext();
        }
        $this->assertSame('$v->pub', $properties['pub']->access_path);
        $this->assertSame('$v->pro', $properties['pro']->access_path);
        $this->assertNull($properties['pri']->access_path);
        $this->assertSame('$v->pub2', $properties['pub2']->access_path);
        $this->assertSame('$v->pro2', $properties['pro2']->access_path);
        $this->assertSame('$v->pri2', $properties['pri2']->access_path);

        $p = new Parser(0, TestClass::class);
        $o = $p->parse($v, clone $b);
        $properties = [];
        foreach ($o->getChildren() as $prop) {
            $properties[$prop->getContext()->getName()] = $prop->getContext();
        }
        $this->assertSame('$v->pub', $properties['pub']->access_path);
        $this->assertSame('$v->pro', $properties['pro']->access_path);
        $this->assertSame('$v->pri', $properties['pri']->access_path);
        $this->assertSame('$v->pub2', $properties['pub2']->access_path);
        $this->assertSame('$v->pro2', $properties['pro2']->access_path);
        $this->assertNull($properties['pri2']->access_path);

        $p = new Parser(0, TestClass::class);
        $b->access_path = null;
        $o = $p->parse($v, clone $b);
        $properties = [];
        foreach ($o->getChildren() as $prop) {
            $properties[$prop->getContext()->getName()] = $prop->getContext();
        }
        $this->assertNull($properties['pub']->access_path);
        $this->assertNull($properties['pro']->access_path);
        $this->assertNull($properties['pri']->access_path);
        $this->assertNull($properties['pub2']->access_path);
        $this->assertNull($properties['pro2']->access_path);
        $this->assertNull($properties['pri2']->access_path);
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseObjectVirtual()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing virtual properties below 8.4');
        }

        $p = new Parser();
        $b = new BaseContext('Object');
        $b->access_path = '$v';
        $v = new Php84TestClass();

        $o = $p->parse($v, $b);

        $this->assertInstanceOf(VirtualValue::class, $o->getChildren()[3]);
        $this->assertSame('virtual', $o->getChildren()[3]->getType());
        $this->assertInstanceOf(PropertyContext::class, $o->getChildren()[3]->getContext());
        $this->assertSame('d', $o->getChildren()[3]->getDisplayName());
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseHooks()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing hooks below 8.4');
        }

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php84TestClass();

        $p = new Parser();
        $o = $p->parse($v, clone $b);
        $props = $o->getChildren();

        $this->assertInstanceOf(UninitializedValue::class, $props[0]);
        $this->assertSame('uninitialized', $props[0]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[0]->getContext());
        $this->assertSame('a', $props[0]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props[0]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_GET | PropertyContext::HOOK_SET, $props[0]->getContext()->hooks);
        $this->assertSame('int', $props[0]->getContext()->hook_set_type);

        $this->assertInstanceOf(UninitializedValue::class, $props[1]);
        $this->assertSame('uninitialized', $props[1]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[1]->getContext());
        $this->assertSame('b', $props[1]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $props[1]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_GET, $props[1]->getContext()->hooks);
        $this->assertNull($props[1]->getContext()->hook_set_type);

        $this->assertInstanceOf(UninitializedValue::class, $props[2]);
        $this->assertSame('uninitialized', $props[2]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[2]->getContext());
        $this->assertSame('c', $props[2]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $props[2]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_SET, $props[2]->getContext()->hooks);
        $this->assertSame('int', $props[2]->getContext()->hook_set_type);

        $this->assertInstanceOf(VirtualValue::class, $props[3]);
        $this->assertSame('virtual', $props[3]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[3]->getContext());
        $this->assertSame('d', $props[3]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $props[3]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_GET | PropertyContext::HOOK_SET | PropertyContext::HOOK_SET_TYPE, $props[3]->getContext()->hooks);
        $this->assertSame('int|float', $props[3]->getContext()->hook_set_type);

        $this->assertInstanceOf(VirtualValue::class, $props[4]);
        $this->assertSame('virtual', $props[4]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[4]->getContext());
        $this->assertSame('e', $props[4]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $props[4]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_GET | PropertyContext::HOOK_GET_REF, $props[4]->getContext()->hooks);
        $this->assertNull($props[4]->getContext()->hook_set_type);

        $this->assertInstanceOf(VirtualValue::class, $props[5]);
        $this->assertSame('virtual', $props[5]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[5]->getContext());
        $this->assertSame('f', $props[5]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props[5]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_SET, $props[5]->getContext()->hooks);
        $this->assertNull($props[5]->getContext()->hook_set_type);

        $r = new ReflectionObject($v);
        $v->a = 5;
        $v->f = 10;
        $r->getProperty('c')->setValue($v, 4);

        $o = $p->parse($v, clone $b);
        $props = $o->getChildren();

        $this->assertInstanceOf(FixedWidthValue::class, $props[0]);
        $this->assertSame('integer', $props[0]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[0]->getContext());
        $this->assertSame('a', $props[0]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props[0]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_GET | PropertyContext::HOOK_SET, $props[0]->getContext()->hooks);
        $this->assertSame('int', $props[0]->getContext()->hook_set_type);
        $this->assertSame(6, $props[0]->getValue());

        $this->assertInstanceOf(FixedWidthValue::class, $props[0]);
        $this->assertSame('integer', $props[1]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[1]->getContext());
        $this->assertSame('b', $props[1]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $props[1]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_GET, $props[1]->getContext()->hooks);
        $this->assertNull($props[1]->getContext()->hook_set_type);
        $this->assertSame(9, $props[1]->getValue());

        $this->assertInstanceOf(FixedWidthValue::class, $props[0]);
        $this->assertSame('integer', $props[2]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[2]->getContext());
        $this->assertSame('c', $props[2]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $props[2]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_SET, $props[2]->getContext()->hooks);
        $this->assertSame('int', $props[2]->getContext()->hook_set_type);
        $this->assertSame(3, $props[2]->getValue());

        $this->assertInstanceOf(VirtualValue::class, $props[3]);
        $this->assertSame('virtual', $props[3]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[3]->getContext());
        $this->assertSame('d', $props[3]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $props[3]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_GET | PropertyContext::HOOK_SET | PropertyContext::HOOK_SET_TYPE, $props[3]->getContext()->hooks);
        $this->assertSame('int|float', $props[3]->getContext()->hook_set_type);

        $this->assertInstanceOf(VirtualValue::class, $props[4]);
        $this->assertSame('virtual', $props[4]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[4]->getContext());
        $this->assertSame('e', $props[4]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $props[4]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_GET | PropertyContext::HOOK_GET_REF, $props[4]->getContext()->hooks);
        $this->assertNull($props[4]->getContext()->hook_set_type);

        $this->assertInstanceOf(VirtualValue::class, $props[5]);
        $this->assertSame('virtual', $props[5]->getType());
        $this->assertInstanceOf(PropertyContext::class, $props[5]->getContext());
        $this->assertSame('f', $props[5]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props[5]->getContext()->access);
        $this->assertSame(PropertyContext::HOOK_SET, $props[5]->getContext()->hooks);
        $this->assertNull($props[5]->getContext()->hook_set_type);
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseAsymmetricVisibility()
    {
        if (!KINT_PHP84) {
            $this->markTestSkipped('Not testing asymmetric property visilibity below 8.4');
        }

        $b = new BaseContext('$v');
        $b->access_path = '$v';
        $v = new Php84TestClass();

        $p = new Parser();
        $o = $p->parse($v, clone $b);
        $props = $o->getChildren();

        $this->assertSame('g', $props[6]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props[6]->getContext()->access);
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $props[6]->getContext()->access_set);

        $this->assertSame('h', $props[7]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props[7]->getContext()->access);
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $props[7]->getContext()->access_set);

        $this->assertSame('i', $props[8]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props[8]->getContext()->access);
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $props[8]->getContext()->access_set);

        $this->assertSame('j', $props[9]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PUBLIC, $props[9]->getContext()->access);
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $props[9]->getContext()->access_set);

        $this->assertSame('k', $props[10]->getContext()->name);
        $this->assertSame(ClassDeclaredContext::ACCESS_PROTECTED, $props[10]->getContext()->access);
        $this->assertSame(ClassDeclaredContext::ACCESS_PRIVATE, $props[10]->getContext()->access_set);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseResourceClosed
     */
    public function testParseResourceClosed()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = \fopen(__FILE__, 'r');
        \fclose($v);

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf(ClosedResourceValue::class, $o);
        $this->assertSame('resource (closed)', $o->getType());
        $this->assertSame('closed resource', $o->getDisplayType());
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseReferences()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $r = 1234;
        $v = [&$r, 1234, new stdClass()];

        $o = $p->parse($v, clone $b);

        $this->assertTrue($o->getContents()[0]->getContext()->isRef());
        $this->assertFalse($o->getContents()[1]->getContext()->isRef());
        $this->assertFalse($o->getContents()[2]->getContext()->isRef());

        $v = new stdClass();
        $v->v1 = &$r;
        $v->v2 = 1234;
        $v->v3 = new stdClass();

        $o = $p->parse($v, clone $b);

        $this->assertTrue($o->getChildren()[0]->getContext()->isRef());
        $this->assertFalse($o->getChildren()[1]->getContext()->isRef());
        $this->assertFalse($o->getChildren()[2]->getContext()->isRef());

        $propval = 'test';
        $v = new Php74TestClass();
        $v->b = &$propval;

        $o = $p->parse($v, clone $b);

        foreach ($o->getChildren() as $val) {
            $this->assertSame('b' === $val->getDisplayName(), $val->getContext()->isRef());
        }

        $v = new Php74TestClass();
        $v->b = 'test';
        $a = [
            'testval' => $v->b,
            'testref' => &$v->b,
        ];

        $o = $p->parse($a, clone $b);

        foreach ($o->getContents() as $val) {
            $this->assertSame('testref' === $val->getDisplayName(), $val->getContext()->isRef());
        }
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseRecursion()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = [];
        $v[] = &$v;

        $recursed = false;

        $pl = new ProxyPlugin(
            ['array', 'object'],
            Parser::TRIGGER_RECURSION,
            function (&$var, $v) use (&$recursed) {
                $recursed = true;

                return $v;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertEquals(true, $o->getContents()[0]->flags & AbstractValue::FLAG_RECURSION);
        $this->assertTrue($recursed);

        $v = new stdClass();
        $v->v = $v;

        $recursed = false;

        $o = $p->parse($v, clone $b);

        $this->assertEquals(true, $o->getChildren()[0]->flags & AbstractValue::FLAG_RECURSION);
        $this->assertTrue($recursed);
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseDepthLimit()
    {
        $p = new Parser(1);
        $b = new BaseContext('$v');
        $v = [[1234]];

        $limit = false;

        $pl = new ProxyPlugin(
            ['array', 'object'],
            Parser::TRIGGER_DEPTH_LIMIT,
            function (&$var, $v) use (&$limit) {
                $limit = true;

                return $v;
            }
        );
        $p->addPlugin($pl);

        $limit = false;
        $o = $p->parse($v, clone $b);

        $this->assertEquals(true, $o->getContents()[0]->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertTrue($limit);

        $v = new stdClass();
        $v->v = new stdClass();
        $v->v->v = new stdClass();

        $limit = false;
        $o = $p->parse($v, clone $b);

        $this->assertEquals(true, $o->getChildren()[0]->flags & AbstractValue::FLAG_DEPTH_LIMIT);
        $this->assertTrue($limit);
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseCastKeys()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
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
        $this->assertSame('1', $o1->getDisplaySize());
        $this->assertSame('value', $o1->getChildren()[0]->getValue());
        $this->assertSame('$v->{\'0\'}', $o1->getChildren()[0]->getContext()->getAccessPath());
        $this->assertTrue(isset($v1->{'0'}));
        $this->assertSame('0', $o1->getChildren()[0]->getContext()->getName());
        $this->assertSame('0', $o1->getChildren()[0]->getDisplayName());

        // Normal object
        $this->assertSame('1', $o2->getDisplaySize());
        $this->assertSame('value', $o2->getChildren()[0]->getValue());
        $this->assertSame('$v->{\'0\'}', $o2->getChildren()[0]->getContext()->getAccessPath());
        $this->assertTrue(isset($v2->{'0'}));
        $this->assertSame('0', $o2->getChildren()[0]->getContext()->getName());
        $this->assertSame('0', $o2->getChildren()[0]->getDisplayName());

        // Array from object
        $this->assertSame('1', $o3->getDisplaySize());
        $this->assertSame('value', $o3->getContents()[0]->getValue());
        $this->assertSame('$v[0]', $o3->getContents()[0]->getContext()->getAccessPath());
        $this->assertTrue(isset($v3['0']));
        $this->assertSame(0, $o3->getContents()[0]->getContext()->getName());
        $this->assertSame('0', $o3->getContents()[0]->getDisplayName());

        // Normal array
        $this->assertSame('1', $o4->getDisplaySize());
        $this->assertSame('value', $o4->getContents()[0]->getValue());
        $this->assertSame('$v[0]', $o4->getContents()[0]->getContext()->getAccessPath());
        $this->assertTrue(isset($v4['0']));
        $this->assertSame(0, $o4->getContents()[0]->getContext()->getName());
        $this->assertSame('0', $o4->getContents()[0]->getDisplayName());

        // Object with both
        $this->assertSame('1', $o5->getDisplaySize());
        $this->assertSame('value2', $o5->getChildren()[0]->getValue());
        $this->assertSame('$v->{\'0\'}', $o5->getChildren()[0]->getContext()->getAccessPath());
        $this->assertSame('0', $o5->getChildren()[0]->getContext()->getName());
        $this->assertSame('0', $o5->getChildren()[0]->getDisplayName());

        // Array with both
        $this->assertSame('1', $o6->getDisplaySize());
        $this->assertSame('value2', $o6->getContents()[0]->getValue());
        $this->assertSame('$v[0]', $o6->getContents()[0]->getContext()->getAccessPath());
        $this->assertSame(0, $o6->getContents()[0]->getContext()->getName());
        $this->assertSame('0', $o6->getContents()[0]->getDisplayName());

        // Object with both and weak equality (As of PHP 7.2)
        $v7 = (object) ['value'];
        $v7->{'0'} = 'value2';
        $v7->{''} = 'value3';
        $o7 = $p->parse($v7, clone $b);

        // Object with both and weak equality
        $this->assertSame('2', $o7->getDisplaySize());
        foreach ($o7->getChildren() as $o) {
            $this->assertContains($o->getValue(), ['value2', 'value3']);

            if ('value2' === $o->getValue()) {
                $this->assertSame('$v->{\'0\'}', $o->getContext()->getAccessPath());
                $this->assertSame('0', $o->getContext()->getName());
                $this->assertSame('0', $o->getDisplayName());
            } elseif ('value3' === $o->getValue()) {
                $this->assertSame('$v->{\'\'}', $o->getContext()->getAccessPath());
                $this->assertSame('', $o->getContext()->getName());
                $this->assertSame('', $o->getDisplayName());
            }
        }
    }

    /**
     * @covers \Kint\Parser\Parser::addPlugin
     * @covers \Kint\Parser\Parser::applyPluginsComplete
     * @covers \Kint\Parser\Parser::clearPlugins
     */
    public function testPlugins()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $this->assertObjectNotHasProperty('testPluginCorrectlyActivated', $o);

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_SUCCESS,
            function (&$var, $v) {
                $v->flags |= 1024;

                return $v;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertEquals(true, $o->flags & 1024);

        $p->clearPlugins();

        $o = $p->parse($v, clone $b);

        $this->assertEquals(false, $o->flags & 1024);

        $pl = new ProxyPlugin(
            [],
            Parser::TRIGGER_SUCCESS,
            function () {}
        );
        $p->addPlugin($pl);

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_NONE,
            function () {}
        );
        $p->addPlugin($pl);

        $p->parse($v, clone $b);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::applyPluginsBegin
     * @covers \Kint\Parser\Parser::applyPluginsComplete
     */
    public function testPluginBeginEarlyReturn()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = 1234;

        $o = $p->parse($v, $b);
        $this->assertNull($o->getHint());

        $complete_ran = false;
        $begin_ran = false;
        $early_return = false;

        $p->addPlugin(new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_SUCCESS,
            function (&$var, $v) use (&$complete_ran) {
                $complete_ran = true;
                $v->flags = 1024;

                return $v;
            }
        ));

        $p->addPlugin(new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_BEGIN,
            function (&$var, $c) use (&$begin_ran, &$early_return) {
                $begin_ran = true;
                $v = new FixedWidthValue($c, 1234);
                $v->flags = 2048;

                return $early_return ? $v : null;
            }
        ));

        $o = $p->parse($v, $b);
        $this->assertSame(1024, $o->flags);
        $this->assertTrue($complete_ran);
        $this->assertTrue($begin_ran);

        $complete_ran = false;
        $begin_ran = false;
        $early_return = true;

        $o = $p->parse($v, $b);
        $this->assertSame(2048, $o->flags);
        $this->assertFalse($complete_ran);
        $this->assertTrue($begin_ran);
    }

    /**
     * @covers \Kint\Parser\Parser::addPlugin
     */
    public function testBadBeginPlugin()
    {
        $p = new Parser();

        $plugin = new class($p) extends AbstractPlugin {
            public function getTriggers(): int
            {
                return Parser::TRIGGER_BEGIN;
            }

            public function getTypes(): array
            {
                return ['string'];
            }
        };

        $this->expectException(InvalidArgumentException::class);

        $p->addPlugin($plugin);
    }

    /**
     * @covers \Kint\Parser\Parser::addPlugin
     */
    public function testBadCompletePlugin()
    {
        $p = new Parser();

        $plugin = new class($p) extends AbstractPlugin {
            public function getTriggers(): int
            {
                return Parser::TRIGGER_SUCCESS;
            }

            public function getTypes(): array
            {
                return ['string'];
            }
        };

        $this->expectException(InvalidArgumentException::class);

        $p->addPlugin($plugin);
    }

    /**
     * @covers \Kint\Parser\Parser::addPlugin
     * @covers \Kint\Parser\Parser::applyPluginsBegin
     * @covers \Kint\Parser\Parser::applyPluginsComplete
     */
    public function testTriggers()
    {
        $p = new Parser(1);
        $b = new BaseContext('$v');
        $v = [1234, [1234]];
        $v[] = &$v;

        $triggers = [];

        $pl = new ProxyPlugin(
            ['integer', 'array'],
            Parser::TRIGGER_BEGIN | Parser::TRIGGER_COMPLETE,
            function (&$var, $o, $trig) use (&$triggers) {
                $triggers[] = $trig;

                return $trig & Parser::TRIGGER_BEGIN ? null : $o;
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
     * @covers \Kint\Parser\Parser::applyPluginsBegin
     */
    public function testPluginsBeginExceptionBecomesWarning()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = 1234;

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_BEGIN,
            function () {
                throw new Exception('begin');
            }
        );
        $p->addPlugin($pl);

        $o = @$p->parse($v, clone $b);
        $this->assertInstanceOf(AbstractValue::class, $o);

        try {
            $o = $p->parse($v, clone $b);
        } catch (Warning $w) {
            $this->assertStringEndsWith('message: begin', $w->getMessage());

            return;
        }

        $this->fail("Didn't get warning");
    }

    /**
     * @covers \Kint\Parser\Parser::applyPluginsComplete
     */
    public function testPluginsCompleteExceptionBecomesWarning()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = 1234;

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_COMPLETE,
            function () {
                throw new Exception('complete');
            }
        );
        $p->addPlugin($pl);

        $o = @$p->parse($v, clone $b);
        $this->assertInstanceOf(AbstractValue::class, $o);

        try {
            $p->parse($v, clone $b);
        } catch (Warning $w) {
            $this->assertStringEndsWith('message: complete', $w->getMessage());

            return;
        }

        $this->fail("Didn't get warning");
    }

    public function testPluginsExceptionCleansUpRecursionMarkers()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $v = new stdClass();
        $v->v = $v;

        $pl = new ProxyPlugin(
            ['object', 'array'],
            Parser::TRIGGER_COMPLETE,
            static function (&$var, AbstractValue $v) {
                if ($v->getContext()->getDepth()) {
                    throw new Exception();
                }
            }
        );
        $p->addPlugin($pl);

        try {
            $o = $p->parse($v, clone $b);
        } catch (Exception $e) {
        }

        $p->clearPlugins();

        $o = $p->parse($v, clone $b);
        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_RECURSION);
        $this->assertEquals(true, $o->getChildren()[0]->flags & AbstractValue::FLAG_RECURSION);

        $v = [];
        $v[] = &$v;

        $p->addPlugin($pl);

        try {
            $o = $p->parse($v, clone $b);
        } catch (Exception $e) {
        }

        $p->clearPlugins();

        $o = $p->parse($v, clone $b);
        $this->assertEquals(false, $o->flags & AbstractValue::FLAG_RECURSION);
        $this->assertEquals(true, $o->getContents()[0]->flags & AbstractValue::FLAG_RECURSION);
    }
}
