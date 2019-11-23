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

use DomainException;
use Exception;
use Kint\Object\BasicObject;
use Kint\Object\InstanceObject;
use Kint\Object\Representation\Representation;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Test\Fixtures\ChildTestClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;

class ParserTest extends TestCase
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
        $marker = new ReflectionProperty('Kint\\Parser\\Parser', 'marker');

        $marker->setAccessible(true);

        $p1 = new Parser();

        $this->assertFalse($p1->getDepthLimit());
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

        $p->setDepthLimit(false);
        $this->assertFalse($p->getDepthLimit());
    }

    /**
     * @covers \Kint\Parser\Parser::noRecurseCall
     */
    public function testNoRecurseCall()
    {
        $p = new Parser();
        $p->setDepthLimit(42);

        $p2 = new Parser();
        $this->assertFalse($p2->getDepthLimit());

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_COMPLETE,
            function () use ($p2) {
                $p2->setDepthLimit(43);
            }
        );
        $p->addPlugin($pl);

        $v = 4;
        $o = $p->parse($v, BasicObject::blank());

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
        $o = $p->parse($v, BasicObject::blank());

        $this->assertTrue($success, 'Failed to throw domain exception on recursed call');
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseGeneric
     */
    public function testParseInteger()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v', '$v');
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $this->assertSame('$v', $o->access_path);
        $this->assertSame('$v', $o->name);
        $this->assertSame('integer', $o->type);
        $this->assertSame('Kint\\Object\\BasicObject', \get_class($o));
        $this->assertSame('Kint\\Object\\Representation\\Representation', \get_class($o->value));
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
        $b = BasicObject::blank();
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
        $b = BasicObject::blank();
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
        $b = BasicObject::blank();
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
        $b = BasicObject::blank();
        $v = 'The quick brown fox jumps over the lazy dog';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf('Kint\\Object\\BlobObject', $o);

        $this->assertSame('string', $o->type);
        $this->assertSame($v, $o->value->contents);
        $this->assertTrue($o->value->implicit_label);
        $this->assertSame('ASCII', $o->encoding);
        $this->assertSame(\strlen($v), $o->size);
        $this->assertContains('string', $o->hints);

        // Apologies to Spanish programmers, Google made this sentence.
        $v = 'El zorro marrón rápido salta sobre el perro perezoso';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf('Kint\\Object\\BlobObject', $o);

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
        $b = BasicObject::blank();
        $v = \fopen(__FILE__, 'r');

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf('Kint\\Object\\ResourceObject', $o);

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
        $b = BasicObject::blank('List', '$v');
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
        $this->assertSame(BasicObject::OPERATOR_ARRAY, $val[0]->operator);
        $this->assertSame('key', $val[1]->name);
        $this->assertSame('value', $val[1]->value->contents);
        $this->assertSame('$v[\'key\']', $val[1]->access_path);
        $this->assertSame(BasicObject::OPERATOR_ARRAY, $val[1]->operator);
        $this->assertSame(1234, $val[2]->name);
        $this->assertSame(5678, $val[2]->value->contents);
        $this->assertSame('$v[1234]', $val[2]->access_path);
        $this->assertSame(BasicObject::OPERATOR_ARRAY, $val[2]->operator);

        $v = [];

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf('Kint\\Object\\Representation\\Representation', $o->value);
        $this->assertCount(0, $o->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseObject()
    {
        $p = new Parser();
        $b = BasicObject::blank('List', '$v');
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf('Kint\\Object\\InstanceObject', $o);

        $this->assertSame('object', $o->type);
        $this->assertSame('List', $o->name);
        $this->assertSame('Kint\\Test\\Fixtures\\ChildTestClass', $o->classname);
        $this->assertSame(\spl_object_hash($v), $o->hash);
        $this->assertContains('object', $o->hints);

        $val = \array_values($o->value->contents);

        $this->assertSame('pub', $val[0]->name);
        $this->assertSame('array', $val[0]->type);
        $this->assertSame(BasicObject::OPERATOR_OBJECT, $val[0]->operator);
        $this->assertSame('$v->pub', $val[0]->access_path);
        $this->assertSame('pro', $val[1]->name);
        $this->assertSame('array', $val[1]->type);
        $this->assertSame(BasicObject::OPERATOR_OBJECT, $val[1]->operator);
        $this->assertNull($val[1]->access_path);
        $this->assertSame('pri', $val[2]->name);
        $this->assertSame('array', $val[2]->type);
        $this->assertSame(BasicObject::OPERATOR_OBJECT, $val[2]->operator);
        $this->assertNull($val[2]->access_path);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseResourceClosed
     */
    public function testParseUnknown()
    {
        $p = new Parser();
        $b = BasicObject::blank();
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
        $b = BasicObject::blank();
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
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseRecursion()
    {
        $p = new Parser();
        $b = BasicObject::blank();
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

        $this->assertContains('recursion', $o->value->contents[0]->hints);
        $this->assertTrue($recursed);

        $v = new stdClass();
        $v->v = $v;

        $recursed = false;

        $o = $p->parse($v, clone $b);

        $this->assertContains('recursion', $o->value->contents[0]->hints);
        $this->assertTrue($recursed);
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseDeep
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseDepthLimit()
    {
        $p = new Parser(1);
        $b = BasicObject::blank();
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

        $this->assertContains('depth_limit', $o->value->contents[0]->hints);
        $this->assertTrue($limit);

        $limit = false;

        $v = new stdClass();
        $v->v = 1234;
        $v = [$v];

        $o = $p->parse($v, clone $b);

        $this->assertContains('depth_limit', $o->value->contents[0]->hints);
        $this->assertTrue($limit);

        $limit = false;

        $o = $p->parseDeep($v, clone $b);

        $this->assertNotContains('depth_limit', $o->value->contents[0]->hints);
        $this->assertFalse($limit);
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseCastKeys()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v', '$v');

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

        if (\version_compare(PHP_VERSION, '7.2') >= 0) {
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
        } else {
            // Object from array
            $this->assertSame(1, $o1->size);
            $this->assertSame('value', $o1->value->contents[0]->value->contents);
            $this->assertSame('array_values((array) $v)[0]', $o1->value->contents[0]->access_path);
            $this->assertFalse(isset($v1->{'0'}));
            $this->assertSame(0, $o1->value->contents[0]->name);

            // Normal object
            $this->assertSame(1, $o2->size);
            $this->assertSame('value', $o2->value->contents[0]->value->contents);
            $this->assertSame('$v->{\'0\'}', $o2->value->contents[0]->access_path);
            $this->assertTrue(isset($v2->{'0'}));
            $this->assertSame('0', $o2->value->contents[0]->name);

            // Array from object
            $this->assertSame(1, $o3->size);
            $this->assertSame('value', $o3->value->contents[0]->value->contents);
            $this->assertSame('array_values($v)[0]', $o3->value->contents[0]->access_path);
            $this->assertFalse(isset($v3['0']));
            $this->assertSame('0', $o3->value->contents[0]->name);

            // Normal array
            $this->assertSame(1, $o4->size);
            $this->assertSame('value', $o4->value->contents[0]->value->contents);
            $this->assertSame('$v[0]', $o4->value->contents[0]->access_path);
            $this->assertTrue(isset($v4['0']));
            $this->assertSame(0, $o4->value->contents[0]->name);

            // Object with both
            $this->assertSame(2, $o5->size);
            foreach ($o5->value->contents as $o) {
                $this->assertContains($o->value->contents, ['value', 'value2']);

                if ('value' === $o->value->contents) {
                    $this->assertSame('array_values((array) $v)[0]', $o->access_path);
                    $this->assertSame(0, $o->name);
                } elseif ('value2' === $o->value->contents) {
                    $this->assertSame('$v->{\'0\'}', $o->access_path);
                    $this->assertSame('0', $o->name);
                }
            }

            // Array with both
            $this->assertSame(2, $o6->size);
            foreach ($o6->value->contents as $o) {
                $this->assertContains($o->value->contents, ['value', 'value2']);

                if ('value' === $o->value->contents) {
                    $this->assertSame('array_values($v)[0]', $o->access_path);
                    $this->assertSame('0', $o->name);
                } elseif ('value2' === $o->value->contents) {
                    $this->assertSame('$v[0]', $o->access_path);
                    $this->assertSame(0, $o->name);
                }
            }
        }
    }

    /**
     * @covers \Kint\Parser\Parser::childHasPath
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseAccessPathAvailability()
    {
        $b = BasicObject::blank('$v', '$v');
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

        $p = new Parser(false, 'Kint\\Test\\Fixtures\\ChildTestClass');
        $o = $p->parse($v, clone $b);
        $properties = [];
        foreach ($o->value->contents as $prop) {
            $properties[$prop->name] = $prop;
        }
        $this->assertSame('$v->pub', $properties['pub']->access_path);
        $this->assertSame('$v->pro', $properties['pro']->access_path);
        $this->assertNull($properties['pri']->access_path);

        $p = new Parser(false, 'Kint\\Test\\Fixtures\\TestClass');
        $o = $p->parse($v, clone $b);
        $properties = [];
        foreach ($o->value->contents as $prop) {
            $properties[$prop->name] = $prop;
        }
        $this->assertSame('$v->pub', $properties['pub']->access_path);
        $this->assertSame('$v->pro', $properties['pro']->access_path);
        $this->assertSame('$v->pri', $properties['pri']->access_path);
    }

    /**
     * @covers \Kint\Parser\Parser::addPlugin
     * @covers \Kint\Parser\Parser::applyPlugins
     * @covers \Kint\Parser\Parser::clearPlugins
     */
    public function testPlugins()
    {
        $p = new Parser();
        $b = BasicObject::blank();
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $this->assertObjectNotHasAttribute('testPluginCorrectlyActivated', $o);

        $pl = new ProxyPlugin(
            ['integer'],
            Parser::TRIGGER_SUCCESS,
            function (&$var, &$o) {
                $o->testPluginCorrectlyActivated = true;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertObjectHasAttribute('testPluginCorrectlyActivated', $o);

        $p->clearPlugins();

        $o = $p->parse($v, clone $b);

        $this->assertObjectNotHasAttribute('testPluginCorrectlyActivated', $o);

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
        $b = BasicObject::blank();
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
        $b = BasicObject::blank();
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

        $this->assertObjectNotHasAttribute('testPluginCorrectlyActivated', $o);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     * @covers \Kint\Parser\Parser::applyPlugins
     */
    public function testPluginExceptionBecomesWarning()
    {
        $p = new Parser();
        $b = BasicObject::blank();
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

        $p->parse($v, clone $b);
    }

    public function childHasPathProvider()
    {
        $data = [];

        $expected = [
            'public parser' => [
                new Parser(),
                [
                    'props' => ['$v', false, true, false, false],
                    'statics' => ['$v', true, true, false, false],
                    'props without path' => [null, false, false, false, false],
                    'statics without path' => [null, true, true, false, false],
                ],
            ],
            'protected parser' => [
                new Parser(false, 'Kint\\Test\\Fixtures\\ChildTestClass'),
                [
                    'props' => ['$v', false, true, true, false],
                    'statics' => ['$v', true, true, true, false],
                    'props without path' => [null, false, false, false, false],
                    'statics without path' => [null, true, true, true, false],
                ],
            ],
            'private parser' => [
                new Parser(false, 'Kint\\Test\\Fixtures\\TestClass'),
                [
                    'props' => ['$v', false, true, true, true],
                    'statics' => ['$v', true, true, true, true],
                    'props without path' => [null, false, false, false, false],
                    'statics without path' => [null, true, true, true, true],
                ],
            ],
        ];

        foreach ($expected as $parser_name => $params) {
            list($parser, $opts) = $params;

            foreach ($opts as $name => $set) {
                list($path, $static, $pub, $pro, $pri) = $set;

                $visibilities = [
                    BasicObject::ACCESS_PUBLIC => $pub,
                    BasicObject::ACCESS_PROTECTED => $pro,
                    BasicObject::ACCESS_PRIVATE => $pri,
                ];

                foreach ($visibilities as $visibility => $expect) {
                    $parent = new InstanceObject();
                    $parent->classname = 'Kint\\Test\\Fixtures\\ChildTestClass';
                    $parent->type = 'object';

                    $r = new Representation('Contents');
                    $parent->addRepresentation($r);

                    $prop = BasicObject::blank();
                    $r->contents = [$prop];
                    $prop->owner_class = 'Kint\\Test\\Fixtures\\TestClass';

                    $parent->access_path = $path;
                    $prop->static = $static;
                    $prop->access = $visibility;

                    $data[$parser_name.', '.$visibility.' '.$name] = [$parser, $parent, $prop, $expect];
                }
            }
        }

        return $data;
    }

    /**
     * @dataProvider childHasPathProvider
     * @covers \Kint\Parser\Parser::childHasPath
     *
     * @param \Kint\Parser\Parser      $parser
     * @param \Kint\Object\BasicObject $parent
     * @param \Kint\Object\BasicObject $child
     * @param bool                     $expected
     */
    public function testChildHasPath($parser, $parent, $child, $expected)
    {
        $this->assertSame($expected, $parser->childHasPath($parent, $child));
    }

    /**
     * @covers \Kint\Parser\Parser::getCleanArray
     */
    public function testGetCleanArray()
    {
        $p = new Parser();
        $b = BasicObject::blank();
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
