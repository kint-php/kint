<?php

namespace Kint\Test\Parser;

use Exception;
use Kint\Object\BasicObject;
use Kint\Object\BlobObject;
use Kint\Object\InstanceObject;
use Kint\Object\Representation\Representation;
use Kint\Object\ResourceObject;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Test\Fixtures\ChildTestClass;
use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

class ParserTest extends PHPUnit_Framework_TestCase
{
    public function testTriggerComplete()
    {
        $this->assertEquals(
            Parser::TRIGGER_SUCCESS |
            Parser::TRIGGER_DEPTH_LIMIT |
            Parser::TRIGGER_RECURSION,
            Parser::TRIGGER_COMPLETE
        );
    }

    /**
     * @covers \Kint\Parser\Parser::__construct
     * @covers \Kint\Parser\Parser::getDepthLimit
     * @covers \Kint\Parser\Parser::getCallerClass
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
        $this->assertNotEquals($marker->getValue($p1), $marker->getValue($p2));
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseGeneric
     */
    public function testParseInteger()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $this->assertEquals('$v', $o->access_path);
        $this->assertEquals('$v', $o->name);
        $this->assertEquals('integer', $o->type);
        $this->assertEquals('Kint\\Object\\BasicObject', get_class($o));
        $this->assertEquals('Kint\\Object\\Representation\\Representation', get_class($o->value));
        $this->assertEquals(1234, $o->value->contents);
        $this->assertEquals(1234, $v);
        $this->assertEquals(0, $o->depth);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseGeneric
     */
    public function testParseBoolean()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = true;

        $o = $p->parse($v, clone $b);

        $this->assertEquals('boolean', $o->type);
        $this->assertEquals(true, $o->value->contents);

        $v = false;

        $o = $p->parse($v, clone $b);

        $this->assertEquals(false, $o->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseGeneric
     */
    public function testParseDouble()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = 1234.5678;

        $o = $p->parse($v, clone $b);

        $this->assertEquals('double', $o->type);
        $this->assertEquals(1234.5678, $o->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseGeneric
     */
    public function testParseNull()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = null;

        $o = $p->parse($v, clone $b);

        $this->assertEquals('null', $o->type);
        $this->assertEquals(null, $o->value->contents);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseString
     */
    public function testParseString()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = 'The quick brown fox jumps over the lazy dog';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf('Kint\\Object\\BlobObject', $o);
        if (!$o instanceof BlobObject) {
            return; // phpstan
        }

        $this->assertEquals('string', $o->type);
        $this->assertEquals($v, $o->value->contents);
        $this->assertEquals(true, $o->value->implicit_label);
        $this->assertEquals('ASCII', $o->encoding);
        $this->assertEquals(strlen($v), $o->size);
        $this->assertContains('string', $o->hints);

        // Apologies to Spanish programmers, Google made this sentence.
        $v = 'El zorro marrón rápido salta sobre el perro perezoso';

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf('Kint\\Object\\BlobObject', $o);
        if (!$o instanceof BlobObject) {
            return; // phpstan
        }

        $this->assertEquals($v, $o->value->contents);
        $this->assertEquals('UTF-8', $o->encoding);
        $this->assertEquals(mb_strlen($v, 'UTF-8'), $o->size);
        $this->assertNotEquals(strlen($v), $o->size);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseResource
     */
    public function testParseResource()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = imagecreate(1, 1);

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf('Kint\\Object\\ResourceObject', $o);
        if (!$o instanceof ResourceObject) {
            return; // phpstan
        }

        $this->assertEquals('resource', $o->type);
        $this->assertEquals(null, $o->value);
        $this->assertEquals('gd', $o->resource_type);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseArray
     */
    public function testParseArray()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = array(
            1234,
            'key' => 'value',
            1234 => 5678,
        );

        $o = $p->parse($v, clone $b);

        $this->assertEquals('array', $o->type);

        $val = array_values($o->value->contents);

        $this->assertEquals(0, $val[0]->name);
        $this->assertEquals(1234, $val[0]->value->contents);
        $this->assertEquals('$v[0]', $val[0]->access_path);
        $this->assertEquals(BasicObject::OPERATOR_ARRAY, $val[0]->operator);
        $this->assertEquals('key', $val[1]->name);
        $this->assertEquals('value', $val[1]->value->contents);
        $this->assertEquals('$v[\'key\']', $val[1]->access_path);
        $this->assertEquals(BasicObject::OPERATOR_ARRAY, $val[1]->operator);
        $this->assertEquals(1234, $val[2]->name);
        $this->assertEquals(5678, $val[2]->value->contents);
        $this->assertEquals('$v[1234]', $val[2]->access_path);
        $this->assertEquals(BasicObject::OPERATOR_ARRAY, $val[2]->operator);

        $v = array();

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
        $b = BasicObject::blank('$v');
        $v = new ChildTestClass();

        $o = $p->parse($v, clone $b);

        $this->assertInstanceOf('Kint\\Object\\InstanceObject', $o);
        if (!$o instanceof InstanceObject) {
            return; // phpstan
        }

        $this->assertEquals('object', $o->type);
        $this->assertEquals('Kint\\Test\\Fixtures\\ChildTestClass', $o->classname);
        $this->assertEquals(spl_object_hash($v), $o->hash);
        $this->assertContains('object', $o->hints);

        $val = array_values($o->value->contents);

        $this->assertEquals('pub', $val[0]->name);
        $this->assertEquals('array', $val[0]->type);
        $this->assertEquals(BasicObject::OPERATOR_OBJECT, $val[0]->operator);
        $this->assertEquals('$v->pub', $val[0]->access_path);
        $this->assertEquals('pro', $val[1]->name);
        $this->assertEquals('array', $val[1]->type);
        $this->assertEquals(BasicObject::OPERATOR_OBJECT, $val[1]->operator);
        $this->assertNull($val[1]->access_path);
        $this->assertEquals('pri', $val[2]->name);
        $this->assertEquals('array', $val[2]->type);
        $this->assertEquals(BasicObject::OPERATOR_OBJECT, $val[2]->operator);
        $this->assertNull($val[2]->access_path);
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::parseUnknown
     */
    public function testParseUnknown()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = imagecreate(1, 1);
        imagedestroy($v);

        $o = $p->parse($v, clone $b);

        $this->assertEquals('unknown', $o->type);
        $this->assertNull($o->value);
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseReferences()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $r = 1234;
        $v = array(&$r, 1234);

        $o = $p->parse($v, clone $b);

        $this->assertEquals(true, $o->value->contents[0]->reference);
        $this->assertEquals(false, $o->value->contents[1]->reference);

        $v = new stdClass();
        $v->v1 = &$r;
        $v->v2 = 1234;

        $o = $p->parse($v, clone $b);

        $this->assertEquals(true, $o->value->contents[0]->reference);
        $this->assertEquals(false, $o->value->contents[1]->reference);
    }

    /**
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseRecursion()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = array();
        $v[] = &$v;

        $recursed = false;

        $pl = new ProxyPlugin(
            array('array', 'object'),
            Parser::TRIGGER_RECURSION,
            function () use (&$recursed) {
                $recursed = true;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertContains('recursion', $o->value->contents[0]->hints);
        $this->assertEquals(true, $recursed);

        $v = new stdClass();
        $v->v = $v;

        $recursed = false;

        $o = $p->parse($v, clone $b);

        $this->assertContains('recursion', $o->value->contents[0]->hints);
        $this->assertEquals(true, $recursed);
    }

    /**
     * @covers \Kint\Parser\Parser::parseDeep
     * @covers \Kint\Parser\Parser::parseArray
     * @covers \Kint\Parser\Parser::parseObject
     */
    public function testParseDepthLimit()
    {
        $p = new Parser(1);
        $b = BasicObject::blank('$v');
        $v = array(array(1234));

        $limit = false;

        $pl = new ProxyPlugin(
            array('array', 'object'),
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
        $v = array($v);

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
        $b = BasicObject::blank('$v');

        // Object from array
        $v1 = (object) array('value');
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
        $v4 = array('value');
        $o4 = $p->parse($v4, clone $b);

        // Object with both
        $v5 = (object) array('value');
        $v5->{0} = 'value2';
        $o5 = $p->parse($v5, clone $b);

        // Array with both
        $v6 = new stdClass();
        $v6->{0} = 'value';
        $v6 = (array) $v6;
        $v6['0'] = 'value2';
        $o6 = $p->parse($v6, clone $b);

        if (version_compare(PHP_VERSION, '7.2') >= 0) {
            // Object from array
            $this->assertEquals(1, $o1->size);
            $this->assertEquals('value', $o1->value->contents[0]->value->contents);
            $this->assertEquals('$v->{\'0\'}', $o1->value->contents[0]->access_path);
            $this->assertTrue(isset($v1->{'0'}));
            $this->assertSame('0', $o1->value->contents[0]->name);

            // Normal object
            $this->assertEquals(1, $o2->size);
            $this->assertEquals('value', $o2->value->contents[0]->value->contents);
            $this->assertEquals('$v->{\'0\'}', $o2->value->contents[0]->access_path);
            $this->assertTrue(isset($v2->{'0'}));
            $this->assertSame('0', $o2->value->contents[0]->name);

            // Array from object
            $this->assertEquals(1, $o3->size);
            $this->assertEquals('value', $o3->value->contents[0]->value->contents);
            $this->assertEquals('$v[0]', $o3->value->contents[0]->access_path);
            $this->assertTrue(isset($v3['0']));
            $this->assertSame(0, $o3->value->contents[0]->name);

            // Normal array
            $this->assertEquals(1, $o4->size);
            $this->assertEquals('value', $o4->value->contents[0]->value->contents);
            $this->assertEquals('$v[0]', $o4->value->contents[0]->access_path);
            $this->assertTrue(isset($v4['0']));
            $this->assertSame(0, $o4->value->contents[0]->name);

            // Object with both
            $this->assertEquals(1, $o5->size);
            $this->assertEquals('value2', $o5->value->contents[0]->value->contents);
            $this->assertEquals('$v->{\'0\'}', $o5->value->contents[0]->access_path);
            $this->assertSame('0', $o5->value->contents[0]->name);

            // Array with both
            $this->assertEquals(1, $o6->size);
            $this->assertEquals('value2', $o6->value->contents[0]->value->contents);
            $this->assertEquals('$v[0]', $o6->value->contents[0]->access_path);
            $this->assertSame(0, $o6->value->contents[0]->name);

            // Object with both and weak equality (As of PHP 7.2)
            $v7 = (object) array('value');
            $v7->{'0'} = 'value2';
            $v7->{''} = 'value3';
            $o7 = $p->parse($v7, clone $b);

            // Object with both and weak equality
            $this->assertEquals(2, $o7->size);
            foreach ($o7->value->contents as $o) {
                $this->assertContains($o->value->contents, array('value2', 'value3'));

                if ($o->value->contents === 'value2') {
                    $this->assertEquals('$v->{\'0\'}', $o->access_path);
                    $this->assertSame('0', $o->name);
                } elseif ($o->value->contents === 'value3') {
                    $this->assertEquals('$v->{\'\'}', $o->access_path);
                    $this->assertSame('', $o->name);
                }
            }
        } else {
            // Object from array
            $this->assertEquals(1, $o1->size);
            $this->assertEquals('value', $o1->value->contents[0]->value->contents);
            $this->assertEquals('array_values((array) $v)[0]', $o1->value->contents[0]->access_path);
            $this->assertFalse(isset($v1->{'0'}));
            $this->assertSame(0, $o1->value->contents[0]->name);

            // Normal object
            $this->assertEquals(1, $o2->size);
            $this->assertEquals('value', $o2->value->contents[0]->value->contents);
            $this->assertEquals('$v->{\'0\'}', $o2->value->contents[0]->access_path);
            $this->assertTrue(isset($v2->{'0'}));
            $this->assertSame('0', $o2->value->contents[0]->name);

            // Array from object
            $this->assertEquals(1, $o3->size);
            $this->assertEquals('value', $o3->value->contents[0]->value->contents);
            $this->assertEquals('array_values($v)[0]', $o3->value->contents[0]->access_path);
            $this->assertFalse(isset($v3['0']));
            $this->assertSame('0', $o3->value->contents[0]->name);

            // Normal array
            $this->assertEquals(1, $o4->size);
            $this->assertEquals('value', $o4->value->contents[0]->value->contents);
            $this->assertEquals('$v[0]', $o4->value->contents[0]->access_path);
            $this->assertTrue(isset($v4['0']));
            $this->assertSame(0, $o4->value->contents[0]->name);

            // Object with both
            $this->assertEquals(2, $o5->size);
            foreach ($o5->value->contents as $o) {
                $this->assertContains($o->value->contents, array('value', 'value2'));

                if ($o->value->contents === 'value') {
                    $this->assertEquals('array_values((array) $v)[0]', $o->access_path);
                    $this->assertSame(0, $o->name);
                } elseif ($o->value->contents === 'value2') {
                    $this->assertEquals('$v->{\'0\'}', $o->access_path);
                    $this->assertSame('0', $o->name);
                }
            }

            // Array with both
            $this->assertEquals(2, $o6->size);
            foreach ($o6->value->contents as $o) {
                $this->assertContains($o->value->contents, array('value', 'value2'));

                if ($o->value->contents === 'value') {
                    $this->assertEquals('array_values($v)[0]', $o->access_path);
                    $this->assertSame('0', $o->name);
                } elseif ($o->value->contents === 'value2') {
                    $this->assertEquals('$v[0]', $o->access_path);
                    $this->assertSame(0, $o->name);
                }
            }
        }
    }

    /**
     * @covers \Kint\Parser\Parser::parseObject
     * @covers \Kint\Parser\Parser::childHasPath
     */
    public function testParseAccessPathAvailability()
    {
        $b = BasicObject::blank('$v');
        $v = new ChildTestClass();

        $p = new Parser();
        $o = $p->parse($v, clone $b);
        $properties = array();
        foreach ($o->value->contents as $prop) {
            $properties[$prop->name] = $prop;
        }
        $this->assertEquals('$v->pub', $properties['pub']->access_path);
        $this->assertNull($properties['pro']->access_path);
        $this->assertNull($properties['pri']->access_path);

        $p = new Parser(false, 'Kint\\Test\\Fixtures\\ChildTestClass');
        $o = $p->parse($v, clone $b);
        $properties = array();
        foreach ($o->value->contents as $prop) {
            $properties[$prop->name] = $prop;
        }
        $this->assertEquals('$v->pub', $properties['pub']->access_path);
        $this->assertEquals('$v->pro', $properties['pro']->access_path);
        $this->assertNull($properties['pri']->access_path);

        $p = new Parser(false, 'Kint\\Test\\Fixtures\\TestClass');
        $o = $p->parse($v, clone $b);
        $properties = array();
        foreach ($o->value->contents as $prop) {
            $properties[$prop->name] = $prop;
        }
        $this->assertEquals('$v->pub', $properties['pub']->access_path);
        $this->assertEquals('$v->pro', $properties['pro']->access_path);
        $this->assertEquals('$v->pri', $properties['pri']->access_path);
    }

    /**
     * @covers \Kint\Parser\Parser::applyPlugins
     * @covers \Kint\Parser\Parser::addPlugin
     * @covers \Kint\Parser\Parser::clearPlugins
     */
    public function testPlugins()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = 1234;

        $o = $p->parse($v, clone $b);

        $this->assertObjectNotHasAttribute('testPluginCorrectlyActivated', $o);

        $pl = new ProxyPlugin(
            array('integer'),
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
            array(),
            Parser::TRIGGER_SUCCESS,
            function () {}
        );
        $this->assertFalse($p->addPlugin($pl));

        $pl = new ProxyPlugin(
            array('integer'),
            Parser::TRIGGER_NONE,
            function () {}
        );
        $this->assertFalse($p->addPlugin($pl));
    }

    /**
     * @covers \Kint\Parser\Parser::applyPlugins
     * @covers \Kint\Parser\Parser::addPlugin
     */
    public function testTriggers()
    {
        $p = new Parser(1);
        $b = BasicObject::blank('$v');
        $v = array(1234, array(1234));
        $v[] = &$v;

        $triggers = array();

        $pl = new ProxyPlugin(
            array('integer', 'array'),
            Parser::TRIGGER_BEGIN | Parser::TRIGGER_COMPLETE,
            function (&$var, &$o, $trig) use (&$triggers) {
                $triggers[] = $trig;
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertEquals(
            array(
                Parser::TRIGGER_BEGIN,
                Parser::TRIGGER_BEGIN,
                Parser::TRIGGER_SUCCESS,
                Parser::TRIGGER_BEGIN,
                Parser::TRIGGER_DEPTH_LIMIT,
                Parser::TRIGGER_BEGIN,
                Parser::TRIGGER_RECURSION,
                Parser::TRIGGER_SUCCESS,
            ),
            $triggers
        );
    }

    /**
     * @covers \Kint\Parser\Parser::parse
     * @covers \Kint\Parser\Parser::applyPlugins
     * @covers \Kint\Parser\Parser::haltParse
     */
    public function testHaltParse()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $t = clone $b;
        $t->type = 'integer';
        $v = 1234;

        $pl = new ProxyPlugin(
            array('integer'),
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
            array('integer'),
            Parser::TRIGGER_SUCCESS,
            function (&$var, &$o, $trig, $parser) {
                $parser->haltParse();
            }
        );
        $p->addPlugin($pl);

        $pl = new ProxyPlugin(
            array('integer'),
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
        $b = BasicObject::blank('$v');
        $t = clone $b;
        $t->type = 'integer';
        $v = 1234;

        $message = __FUNCTION__;

        $pl = new ProxyPlugin(
            array('integer'),
            Parser::TRIGGER_BEGIN,
            function (&$var, &$o, $trig, $parser) use ($message) {
                throw new Exception($message);
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);
    }

    public function childHasPathProvider()
    {
        $data = array();

        $expected = array(
            'public parser' => array(
                new Parser(),
                array(
                    'props' => array('$v', false, true, false, false),
                    'statics' => array('$v', true, true, false, false),
                    'props without path' => array(null, false, false, false, false),
                    'statics without path' => array(null, true, true, false, false),
                ),
            ),
            'protected parser' => array(
                new Parser(false, 'Kint\\Test\\Fixtures\\ChildTestClass'),
                array(
                    'props' => array('$v', false, true, true, false),
                    'statics' => array('$v', true, true, true, false),
                    'props without path' => array(null, false, false, false, false),
                    'statics without path' => array(null, true, true, true, false),
                ),
            ),
            'private parser' => array(
                new Parser(false, 'Kint\\Test\\Fixtures\\TestClass'),
                array(
                    'props' => array('$v', false, true, true, true),
                    'statics' => array('$v', true, true, true, true),
                    'props without path' => array(null, false, false, false, false),
                    'statics without path' => array(null, true, true, true, true),
                ),
            ),
        );

        foreach ($expected as $parser_name => $params) {
            list($parser, $opts) = $params;

            foreach ($opts as $name => $set) {
                list($path, $static, $pub, $pro, $pri) = $set;

                $visibilities = array(
                    BasicObject::ACCESS_PUBLIC => $pub,
                    BasicObject::ACCESS_PROTECTED => $pro,
                    BasicObject::ACCESS_PRIVATE => $pri,
                );

                foreach ($visibilities as $visibility => $expect) {
                    $parent = BasicObject::blank();
                    $parent = $parent->transplant(new InstanceObject());
                    $parent->classname = 'Kint\\Test\\Fixtures\\ChildTestClass';
                    $parent->type = 'object';

                    $r = new Representation('Contents');
                    $parent->addRepresentation($r);

                    $prop = BasicObject::blank();
                    $r->contents = array($prop);
                    $prop->owner_class = 'Kint\\Test\\Fixtures\\TestClass';

                    $parent->access_path = $path;
                    $prop->static = $static;
                    $prop->access = $visibility;

                    $data[$parser_name.', '.$visibility.' '.$name] = array($parser, $parent, $prop, $expect);
                }
            }
        }

        return $data;
    }

    /**
     * @dataProvider childHasPathProvider
     * @covers \Kint\Parser\Parser::childHasPath
     */
    public function testChildHasPath($parser, $parent, $child, $expected)
    {
        $this->assertEquals($expected, $parser->childHasPath($parent, $child));
    }

    /**
     * @covers \Kint\Parser\Parser::sortObjectProperties
     */
    public function testSortObjectProperties()
    {
        $p = new Parser();

        $ctc = new ChildTestClass();

        $o = $p->parse($ctc, BasicObject::blank('$ctc'));

        $pub = $o->value->contents[0];
        $pro = $o->value->contents[1];

        $rm = new ReflectionMethod('Kint\\Parser\\Parser', 'sortObjectProperties');
        $rm->setAccessible(true);

        $this->assertEquals(0, $rm->invoke($p, $pub, $pub));

        // Sort by access first
        $this->assertEquals(-1, $rm->invoke($p, $pub, $pro));
        $this->assertEquals(1, $rm->invoke($p, $pro, $pub));

        // With the same access they go by name so they should flip
        $pro->access = $pub->access;
        $this->assertEquals(1, $rm->invoke($p, $pub, $pro));
        $this->assertEquals(-1, $rm->invoke($p, $pro, $pub));

        // With the same name they should go by hierarchy
        $pro->name = $pub->name;
        $pro->owner_class = 'Kint\\Test\\Fixtures\\TestClass';
        $this->assertEquals(-1, $rm->invoke($p, $pub, $pro));
        $this->assertEquals(1, $rm->invoke($p, $pro, $pub));

        // With everything the same they should be more or less equal
        $pro->owner_class = $pub->owner_class;
        $this->assertEquals(0, $rm->invoke($p, $pub, $pro));
        $this->assertEquals(0, $rm->invoke($p, $pro, $pub));
    }

    /**
     * @covers \Kint\Parser\Parser::getCleanArray
     */
    public function testGetCleanArray()
    {
        $p = new Parser();
        $b = BasicObject::blank('$v');
        $v = array(1234);

        $arrays = array();

        $pl = new ProxyPlugin(
            array('array'),
            Parser::TRIGGER_SUCCESS,
            function (&$var, &$o, $trig, $parser) use (&$arrays) {
                $clean = $parser->getCleanArray($var);

                // This here is exactly why you should never alter input
                // variables in plugins and always use getCleanArray
                $var[] = 4321;
                $clean[] = 8765;

                $arrays = array(
                    'var' => $var,
                    'clean' => $clean,
                );
            }
        );
        $p->addPlugin($pl);

        $o = $p->parse($v, clone $b);

        $this->assertEquals(array(1234, 4321), $v);
        $this->assertEquals(array(1234, 8765), $arrays['clean']);
        $this->assertEquals(count($v) + 1, count($arrays['var']));
    }
}
