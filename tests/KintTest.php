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

namespace Kint\Test;

use Kint\Kint;
use Kint\Object\BasicObject;
use Kint\Parser\Parser;
use Kint\Parser\TimestampPlugin;
use Kint\Renderer\TextRenderer;
use Kint\Test\Fixtures\Php56TestClass;
use Kint\Test\Fixtures\TestClass;
use Prophecy\Argument;
use ReflectionClass;
use ReflectionProperty;

class KintTest extends KintTestCase
{
    /**
     * @covers \Kint\Kint::__construct
     * @covers \Kint\Kint::getParser
     * @covers \Kint\Kint::getRenderer
     */
    public function testConstruct()
    {
        $p = new Parser();
        $r = new TextRenderer();

        $k = new Kint($p, $r);

        $this->assertSame($p, $k->getParser());
        $this->assertSame($r, $k->getRenderer());
    }

    /**
     * @covers \Kint\Kint::getParser
     * @covers \Kint\Kint::setParser
     */
    public function testGetSetParser()
    {
        $p = new Parser();
        $p2 = new Parser();

        $k = new Kint($p, new TextRenderer());

        $this->assertSame($p, $k->getParser());
        $k->setParser($p2);
        $this->assertSame($p2, $k->getParser());
        $this->assertNotSame($p, $p2);
    }

    /**
     * @covers \Kint\Kint::getRenderer
     * @covers \Kint\Kint::setRenderer
     */
    public function testGetSetRenderer()
    {
        $r = new TextRenderer();
        $r2 = new TextRenderer();

        $k = new Kint(new Parser(), $r);

        $this->assertSame($r, $k->getRenderer());
        $k->setRenderer($r2);
        $this->assertSame($r2, $k->getRenderer());
        $this->assertNotSame($r, $r2);
    }

    /**
     * @covers \Kint\Kint::setStatesFromStatics
     */
    public function testSetStatesFromStatics()
    {
        $p1 = new TimestampPlugin();
        $p2 = new TimestampPlugin();
        $p3 = new TimestampPlugin();
        $p4 = new TimestampPlugin();

        $statics = array(
            'expanded' => true,
            'return' => true,
            'display_called_from' => true,
            'max_depth' => 42,
            'plugins' => array($p1, $p2, $p3, $p4),
        );

        $parser = $this->prophesize('Kint\\Parser\\Parser');
        $renderer = $this->prophesize('Kint\\Renderer\\TextRenderer');
        $k = new Kint($parser->reveal(), $renderer->reveal());

        $renderer->setStatics($statics)->shouldBeCalledTimes(1);

        $parser->setDepthLimit(42)->shouldBeCalledTimes(1);
        $parser->clearPlugins()->shouldBeCalledTimes(1);

        $renderer->filterParserPlugins(array($p1, $p2, $p3, $p4))->shouldBeCalledTimes(1)->willReturn(array($p1, $p3, $p4));

        // Argument::that is a workaround for a bug in prophet's Argument::is
        $parser->addPlugin(
            Argument::that(function ($arg) use ($p1) {
                return $arg === $p1;
            })
        )->shouldBeCalledTimes(1);
        $parser->addPlugin(
            Argument::that(function ($arg) use ($p3) {
                return $arg === $p3;
            })
        )->shouldBeCalledTimes(1);
        $parser->addPlugin(
            Argument::that(function ($arg) use ($p4) {
                return $arg === $p4;
            })
        )->shouldBeCalledTimes(1);

        $k->setStatesFromStatics($statics);
    }

    /**
     * @covers \Kint\Kint::setStatesFromStatics
     */
    public function testSetStatesFromStaticsStringPlugins()
    {
        $r = new ReflectionProperty('Kint\\Kint', 'plugin_pool');
        $r->setAccessible(true);
        $r->setValue(array());

        $parser = $this->prophesize('Kint\\Parser\\Parser');
        $renderer = $this->prophesize('Kint\\Renderer\\TextRenderer');
        $k = new Kint($parser->reveal(), $renderer->reveal());

        $statics = array(
            'plugins' => array(
                'Kint\\Parser\\TimestampPlugin',
                'Kint\\Parser\\MicrotimePlugin',
            ),
        );

        $renderer->setStatics($statics)->shouldBeCalledTimes(1);

        $parser->setDepthLimit(false)->shouldBeCalledTimes(1);
        $parser->clearPlugins()->shouldBeCalledTimes(1);

        $renderer->filterParserPlugins(Argument::any())->shouldBeCalledTimes(1)->will(function ($args) {
            $out = array();

            foreach ($args[0] as $plugin) {
                if ($plugin instanceof TimestampPlugin) {
                    $out[] = $plugin;
                }
            }

            return $out;
        });

        $parser->addPlugin(Argument::type('Kint\\Parser\\TimestampPlugin'))->shouldBeCalledTimes(1);

        $k->setStatesFromStatics($statics);
    }

    /**
     * @covers \Kint\Kint::setStatesFromStatics
     */
    public function testSetStatesFromStaticsEmpty()
    {
        $parser = $this->prophesize('Kint\\Parser\\Parser');
        $renderer = $this->prophesize('Kint\\Renderer\\TextRenderer');
        $k = new Kint($parser->reveal(), $renderer->reveal());

        $renderer->setStatics(array())->shouldBeCalledTimes(1);

        $parser->setDepthLimit(false)->shouldBeCalledTimes(1);
        $parser->clearPlugins()->shouldBeCalledTimes(1);

        $k->setStatesFromStatics(array());
    }

    /**
     * @covers \Kint\Kint::setStatesFromCallInfo
     */
    public function testSetStatesFromCallInfo()
    {
        $r = new TextRenderer();
        $p = new Parser();
        $k = new Kint($p, $r);

        // Set up defaults
        $k->setStatesFromStatics(array(
            'max_depth' => 42,
        ));

        $k->setStatesFromCallInfo(array('foo' => 'bar'));

        $this->assertSame(array('max_depth' => 42), $r->getStatics());
        $this->assertSame(
            array(
                'params' => null,
                'modifiers' => array(),
                'callee' => null,
                'caller' => null,
                'trace' => array(),
            ),
            $r->getCallInfo()
        );
        $this->assertSame(42, $p->getDepthLimit());
        $this->assertNull($p->getCallerClass());

        $k->setStatesFromCallInfo(array(
            'modifiers' => array('!', '@', '+'),
            'caller' => array(
                'class' => 'test1234',
            ),
        ));

        $this->assertSame(
            array(
                'params' => null,
                'modifiers' => array('!', '@', '+'),
                'callee' => null,
                'caller' => array(
                    'class' => 'test1234',
                ),
                'trace' => array(),
            ),
            $r->getCallInfo()
        );
        $this->assertFalse($p->getDepthLimit());
        $this->assertSame('test1234', $p->getCallerClass());
    }

    /**
     * @covers \Kint\Kint::dumpAll
     */
    public function testDumpAll()
    {
        $parser = $this->prophesize('Kint\\Parser\\Parser');
        $renderer = $this->prophesize('Kint\\Renderer\\TextRenderer');
        $k = new Kint($parser->reveal(), $renderer->reveal());

        $dumpee = $k;
        $base = BasicObject::blank();

        $renderer->preRender()->shouldBeCalledTimes(1)->willReturn('pre.');

        $parser->parse(Argument::is($dumpee), Argument::is($base))->shouldBeCalledTimes(2)->willReturn($base);
        $renderer->render(Argument::is($base))->shouldBeCalledTimes(2)->willReturn('render.');

        $renderer->postRender()->shouldBeCalledTimes(1)->willReturn('post');

        $this->assertSame('pre.render.render.post', $k->dumpAll(array($dumpee, $dumpee), array($base, $base)));
    }

    /**
     * @covers \Kint\Kint::dumpAll
     */
    public function testDumpNothing()
    {
        $parser = $this->prophesize('Kint\\Parser\\Parser');
        $renderer = $this->prophesize('Kint\\Renderer\\TextRenderer');
        $k = new Kint($parser->reveal(), $renderer->reveal());

        $renderer->preRender()->shouldBeCalledTimes(1)->willReturn('pre.');
        $renderer->renderNothing()->shouldBeCalledTimes(1)->willReturn('nothing.');
        $renderer->postRender()->shouldBeCalledTimes(1)->willReturn('post');

        $parser->parse()->shouldNotBeCalled();

        $this->assertSame('pre.nothing.post', $k->dumpAll(array(), array()));
    }

    /**
     * @covers \Kint\Kint::dumpAll
     * @expectedException \InvalidArgumentException
     */
    public function testDumpAllUnmatchingArgs()
    {
        $p = new Parser();
        $r = new TextRenderer();
        $k = new Kint($p, $r);

        $k->dumpAll(array($k), array(BasicObject::blank(), 'bar' => 'baz'));
    }

    /**
     * @covers \Kint\Kint::dumpAll
     * @expectedException \InvalidArgumentException
     */
    public function testDumpAllIncorrectBase()
    {
        $p = new Parser();
        $r = new TextRenderer();
        $k = new Kint($p, $r);

        $k->dumpAll(array($k), array('foo'));
    }

    /**
     * @covers \Kint\Kint::dumpVar
     */
    public function testDumpVar()
    {
        $parser = $this->prophesize('Kint\\Parser\\Parser');
        $renderer = $this->prophesize('Kint\\Renderer\\TextRenderer');
        $k = new Kint($parser->reveal(), $renderer->reveal());

        $dumpee = $k;
        $base = BasicObject::blank();

        $parser->parse(Argument::is($dumpee), Argument::is($base))->shouldBeCalledTimes(1)->willReturn($base);
        $renderer->render(Argument::is($base))->shouldBeCalledTimes(1)->willReturn('render');

        $this->assertSame('render', $k->dumpVar($dumpee, $base));
    }

    /**
     * @covers \Kint\Kint::getStatics
     */
    public function testGetStatics()
    {
        $r = new ReflectionClass('Kint');

        $props = $r->getProperties(ReflectionProperty::IS_STATIC);
        $props_array = array();
        foreach ($props as $prop) {
            if ($prop->isPublic() && $prop->isStatic()) {
                $props_array[$prop->getName()] = $prop->getValue();
            }
        }

        \ksort($props_array);

        $this->assertSame($props_array, $stash = Kint::getStatics());
    }

    public function staticModeProvider()
    {
        return array(
            'no options' => array(
                array(),
                false,
            ),
            'auto without cli' => array(
                array(
                    'enabled_mode' => true,
                    'mode_default' => 42,
                    'cli_detection' => false,
                    'mode_default_cli' => 43,
                    'renderers' => array(
                        42 => 'Kint\\Renderer\\RichRenderer',
                        43 => 'Kint\\Renderer\\CliRenderer',
                        44 => 'Kint\\Renderer\\PlainRenderer',
                    ),
                ),
                'Kint\\Renderer\\RichRenderer',
            ),
            'auto with cli' => array(
                array(
                    'enabled_mode' => true,
                    'mode_default' => 42,
                    'cli_detection' => true,
                    'mode_default_cli' => 43,
                    'renderers' => array(
                        42 => 'Kint\\Renderer\\RichRenderer',
                        43 => 'Kint\\Renderer\\CliRenderer',
                        44 => 'Kint\\Renderer\\PlainRenderer',
                    ),
                ),
                'Kint\\Renderer\\CliRenderer',
            ),
            'specific' => array(
                array(
                    'enabled_mode' => 44,
                    'mode_default' => 42,
                    'cli_detection' => true,
                    'mode_default_cli' => 43,
                    'renderers' => array(
                        42 => 'Kint\\Renderer\\RichRenderer',
                        43 => 'Kint\\Renderer\\CliRenderer',
                        44 => 'Kint\\Renderer\\PlainRenderer',
                    ),
                ),
                'Kint\\Renderer\\PlainRenderer',
            ),
            'disabled' => array(
                array('enabled_mode' => false),
                false,
            ),
            'missing renderer' => array(
                array(
                    'enabled_mode' => 45,
                    'mode_default' => 42,
                    'cli_detection' => true,
                    'mode_default_cli' => 43,
                    'renderers' => array(
                        42 => 'Kint\\Renderer\\RichRenderer',
                        43 => 'Kint\\Renderer\\CliRenderer',
                        44 => 'Kint\\Renderer\\PlainRenderer',
                    ),
                ),
                'Kint\\Renderer\\TextRenderer',
            ),
        );
    }

    /**
     * @covers \Kint\Kint::createFromStatics
     * @dataProvider staticModeProvider
     *
     * @param array        $statics
     * @param false|string $renderer_class
     */
    public function testCreateFromStatics(array $statics, $renderer_class)
    {
        $k = Kint::createFromStatics($statics);

        if ($renderer_class) {
            $this->assertSame($renderer_class, \get_class($k->getRenderer()));
        } else {
            $this->assertNull($k);
        }
    }

    public function baseProvider()
    {
        return array(
            'normal params' => array(
                array(
                    array(
                        'name' => '$a',
                        'path' => '$a',
                        'expression' => false,
                    ),
                    array(
                        'name' => '$b[...]',
                        'path' => '$b[$a]',
                        'expression' => false,
                    ),
                ),
                2,
                array(
                    BasicObject::blank('$a', '$a'),
                    BasicObject::blank('$b[...]', '$b[$a]'),
                ),
            ),
            'blacklisted params' => array(
                array(
                    array(
                        'name' => 'true',
                        'path' => 'true',
                        'expression' => false,
                    ),
                    array(
                        'name' => '[...]',
                        'path' => '[$a, $b, $c]',
                        'expression' => false,
                    ),
                ),
                2,
                array(
                    BasicObject::blank(null, 'true'),
                    BasicObject::blank(null, '[$a, $b, $c]'),
                ),
            ),
            'expression params' => array(
                array(
                    array(
                        'name' => '$a + $b',
                        'path' => '$a + $b',
                        'expression' => true,
                    ),
                    array(
                        'name' => '[...] + $c[...]',
                        'path' => '[$a, $b] + $c[$d]',
                        'expression' => true,
                    ),
                ),
                2,
                array(
                    BasicObject::blank('$a + $b', '($a + $b)'),
                    BasicObject::blank('[...] + $c[...]', '([$a, $b] + $c[$d])'),
                ),
            ),
            'missing params' => array(
                array(),
                2,
                array(
                    BasicObject::blank(null, '$0'),
                    BasicObject::blank(null, '$1'),
                ),
            ),
            'no params' => array(
                array(),
                0,
                array(),
            ),
        );
    }

    /**
     * @covers \Kint\Kint::getBasesFromParamInfo
     * @dataProvider baseProvider
     *
     * @param array $paraminfo
     * @param int   $count
     * @param array $expect
     */
    public function testGetBasesFromParamInfo(array $paraminfo, $count, array $expect)
    {
        $bases = Kint::getBasesFromParamInfo($paraminfo, $count);

        $this->assertEquals($expect, $bases);
    }

    public function getCallInfoProvider()
    {
        $aliases = array(
            array('kint', 'dump'),
            'd',
            's',
        );

        $basetrace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $basetrace[0]['file'] = __FILE__;
        $basetrace[0]['line'] = __LINE__;
        // Apparently DEBUG_BACKTRACE_IGNORE_ARGS isn't enough when the function in question is require
        unset($basetrace[\count($basetrace) - 1]['args']);
        $dumpframe = array(
            'class' => 'Kint',
            'function' => 'dump',
        );

        $data['empty trace'] = array(
            'aliases' => $aliases,
            'trace' => array(),
            'param_count' => 1234,
            'expect' => array(
                'params' => null,
                'modifiers' => array(),
                'callee' => null,
                'caller' => null,
                'trace' => array(),
            ),
        );

        $data['full trace'] = array(
            'aliases' => $aliases,
            'trace' => \array_merge(
                $basetrace,
                array(
                    $dumpframe + array(
                        'file' => TestClass::DUMP_FILE,
                        'line' => TestClass::DUMP_LINE,
                    ),
                ),
                array(
                    array(
                        'function' => 'usort',
                    ),
                ),
                $basetrace
            ),
            'param_count' => 1234,
            'expect' => array(
                'params' => null,
                'modifiers' => array(),
                'callee' => $dumpframe + array(
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE,
                ),
                'caller' => array(
                    'function' => 'usort',
                ),
                'trace' => \array_merge(
                    array(
                        $dumpframe + array(
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE,
                        ),
                    ),
                    $basetrace
                ),
            ),
        );

        $data['internal callee trace'] = $data['full trace'];
        $data['internal callee trace']['aliases'][] = 'usort';
        $data['internal callee trace']['expect']['callee'] = array('function' => 'usort');
        $data['internal callee trace']['expect']['caller'] = $basetrace[0];
        $data['internal callee trace']['expect']['trace'] = \array_merge(
            array(
                array('function' => 'usort'),
            ),
            $basetrace
        );
        $data['internal callee trace']['trace'] = $data['internal callee trace']['expect']['trace'];

        $data['unmatching trace'] = $data['full trace'];
        $data['unmatching trace']['aliases'] = array();
        $data['unmatching trace']['expect']['callee'] = null;
        $data['unmatching trace']['expect']['caller'] = null;
        $data['unmatching trace']['expect']['trace'] = \array_merge(
            $basetrace,
            array(
                $dumpframe + array(
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE,
                ),
            ),
            $basetrace
        );

        $data['trace with params'] = $data['full trace'];
        $data['trace with params']['param_count'] = 3;
        $data['trace with params']['expect']['params'] = array(
            array('name' => '$x', 'path' => '$x', 'expression' => false),
            array('name' => '$y', 'path' => '$y', 'expression' => false),
            array('name' => '$z', 'path' => '$z', 'expression' => false),
        );

        $data['trace with modifiers'] = array(
            'aliases' => $aliases,
            'trace' => \array_merge(
                    array(
                        $dumpframe + array(
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE + 1,
                        ),
                    ),
                    $basetrace
                ),
            'param_count' => 0,
            'expect' => array(
                'params' => array(),
                'modifiers' => array('!', '+'),
                'callee' => $dumpframe + array(
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 1,
                ),
                'caller' => $basetrace[0],
                'trace' => \array_merge(
                    array(
                        $dumpframe + array(
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE + 1,
                        ),
                    ),
                    $basetrace
                ),
            ),
        );

        $data['trace function with modifier'] = array(
            'aliases' => $aliases,
            'trace' => \array_merge(
                    array(
                        array(
                            'function' => 'd',
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE + 2,
                        ),
                    ),
                    $basetrace
                ),
            'param_count' => 1,
            'expect' => array(
                'params' => array(
                    array(
                        'name' => '$x',
                        'path' => '$x',
                        'expression' => false,
                    ),
                ),
                'modifiers' => array('~'),
                'callee' => array(
                    'function' => 'd',
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 2,
                ),
                'caller' => $basetrace[0],
                'trace' => \array_merge(
                    array(
                        array(
                            'function' => 'd',
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE + 2,
                        ),
                    ),
                    $basetrace
                ),
            ),
        );

        $data['trace function with multiple hits'] = array(
            'aliases' => $aliases,
            'trace' => \array_merge(
                    array(
                        array(
                            'function' => 'd',
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE + 3,
                        ),
                    ),
                    $basetrace
                ),
            'param_count' => 1,
            'expect' => array(
                'params' => null,
                'modifiers' => array(),
                'callee' => array(
                    'function' => 'd',
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 3,
                ),
                'caller' => $basetrace[0],
                'trace' => \array_merge(
                    array(
                        array(
                            'function' => 'd',
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE + 3,
                        ),
                    ),
                    $basetrace
                ),
            ),
        );

        if (KINT_PHP56) {
            $data['trace with unpack'] = array(
                'aliases' => $aliases,
                'trace' => \array_merge(
                        array(
                            $dumpframe + array(
                                'file' => Php56TestClass::DUMP_FILE,
                                'line' => Php56TestClass::DUMP_LINE,
                            ),
                        ),
                        $basetrace
                    ),
                'param_count' => 4,
                'expect' => array(
                    'params' => array(
                        array(
                            'name' => '$x',
                            'path' => '$x',
                            'expression' => false,
                        ),
                        array(
                            'name' => '$y',
                            'path' => '$y',
                            'expression' => false,
                        ),
                        array(
                            'name' => 'reset($z)',
                            'path' => 'reset($z)',
                            'expression' => false,
                        ),
                        array(
                            'name' => 'array_values($z)[1]',
                            'path' => 'array_values($z)[1]',
                            'expression' => false,
                        ),
                    ),
                    'modifiers' => array(),
                    'callee' => $dumpframe + array(
                        'file' => Php56TestClass::DUMP_FILE,
                        'line' => Php56TestClass::DUMP_LINE,
                    ),
                    'caller' => $basetrace[0],
                    'trace' => \array_merge(
                        array(
                            $dumpframe + array(
                                'file' => Php56TestClass::DUMP_FILE,
                                'line' => Php56TestClass::DUMP_LINE,
                            ),
                        ),
                        $basetrace
                    ),
                ),
            );

            $data['trace with double unpack'] = array(
                'aliases' => $aliases,
                'trace' => \array_merge(
                        array(
                            $dumpframe + array(
                                'file' => Php56TestClass::DUMP_FILE,
                                'line' => Php56TestClass::DUMP_LINE + 1,
                            ),
                        ),
                        $basetrace
                    ),
                'param_count' => 10,
                'expect' => array(
                    'params' => array(),
                    'modifiers' => array(),
                    'callee' => $dumpframe + array(
                        'file' => Php56TestClass::DUMP_FILE,
                        'line' => Php56TestClass::DUMP_LINE + 1,
                    ),
                    'caller' => $basetrace[0],
                    'trace' => \array_merge(
                        array(
                            $dumpframe + array(
                                'file' => Php56TestClass::DUMP_FILE,
                                'line' => Php56TestClass::DUMP_LINE + 1,
                            ),
                        ),
                        $basetrace
                    ),
                ),
            );

            $data['multiple trace with unpack one match'] = array(
                'aliases' => $aliases,
                'trace' => \array_merge(
                        array(
                            $dumpframe + array(
                                'file' => Php56TestClass::DUMP_FILE,
                                'line' => Php56TestClass::DUMP_LINE + 2,
                            ),
                        ),
                        $basetrace
                    ),
                'param_count' => 1,
                'expect' => array(
                    'params' => array(
                        array(
                            'name' => '$x',
                            'path' => '$x',
                            'expression' => false,
                        ),
                    ),
                    'modifiers' => array(),
                    'callee' => $dumpframe + array(
                        'file' => Php56TestClass::DUMP_FILE,
                        'line' => Php56TestClass::DUMP_LINE + 2,
                    ),
                    'caller' => $basetrace[0],
                    'trace' => \array_merge(
                        array(
                            $dumpframe + array(
                                'file' => Php56TestClass::DUMP_FILE,
                                'line' => Php56TestClass::DUMP_LINE + 2,
                            ),
                        ),
                        $basetrace
                    ),
                ),
            );

            $data['multiple trace with unpack multiple match'] = $data['multiple trace with unpack one match'];
            $data['multiple trace with unpack multiple match']['param_count'] = 2;
            $data['multiple trace with unpack multiple match']['expect']['params'] = null;

            $data['multiple trace with unpack no match'] = $data['multiple trace with unpack one match'];
            $data['multiple trace with unpack no match']['param_count'] = 0;
            $data['multiple trace with unpack no match']['expect']['params'] = null;
        }

        return $data;
    }

    /**
     * @dataProvider getCallInfoProvider
     * @covers \Kint\Kint::getCallInfo
     * @covers \Kint\Kint::getSingleCall
     *
     * @param array $aliases
     * @param array $trace
     * @param int   $param_count
     * @param array $expect
     */
    public function testGetCallInfo($aliases, $trace, $param_count, $expect)
    {
        $this->assertSame($expect, Kint::getCallInfo($aliases, $trace, $param_count));
    }

    public function pathProvider()
    {
        return array(
            'standard file' => array(
                'path' => __FILE__,
                'expect' => '<tests>/KintTest.php',
            ),
            'standard dir' => array(
                'path' => __DIR__,
                'expect' => '<tests>',
            ),
            'parent dir' => array(
                'path' => KINT_DIR,
                'expect' => '<kint>',
            ),
            'sub file' => array(
                'path' => KINT_DIR.'/src//test',
                'expect' => '<kint>/src/test',
            ),
            'common path' => array(
                'path' => \dirname(KINT_DIR).'/test/',
                'expect' => '.../test',
            ),
            'root path' => array(
                'path' => '/',
                'expect' => '/',
            ),
            'no common path' => array(
                'path' => '/asdfasdf/test/',
                'expect' => '/asdfasdf/test',
            ),
        );
    }

    /**
     * @dataProvider pathProvider
     * @covers \Kint\Kint::shortenPath
     *
     * @param string $path
     * @param string $expect
     */
    public function testShortenPath($path, $expect)
    {
        Kint::$app_root_dirs = array(
            KINT_DIR => '<kint>',
            KINT_DIR.'/test' => '<test>',
            '' => '<Nothing!>',
            __DIR__ => '<tests>',
            KINT_DIR.'/tes' => '<tes>',
        );

        $this->assertSame($expect, Kint::shortenPath($path));
    }

    /**
     * @covers \Kint\Kint::getIdeLink
     */
    public function testGetIdeLink()
    {
        Kint::$file_link_format = '<a href="%f:%l">%f:%l</a>';

        $file = \uniqid('', true);
        $line = \uniqid('', true);

        $this->assertSame('<a href="'.$file.':'.$line.'">'.$file.':'.$line.'</a>', Kint::getIdeLink($file, $line));
    }
}
