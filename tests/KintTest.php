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

namespace Kint\Test;

use InvalidArgumentException;
use Kint\Kint;
use Kint\Parser\AbstractPlugin;
use Kint\Parser\ConstructablePluginInterface;
use Kint\Parser\MicrotimePlugin;
use Kint\Parser\Parser;
use Kint\Parser\TimestampPlugin;
use Kint\Renderer\CliRenderer;
use Kint\Renderer\PlainRenderer;
use Kint\Renderer\RichRenderer;
use Kint\Renderer\TextRenderer;
use Kint\Test\Fixtures\Php56TestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Value\Context\BaseContext;
use Kint\Value\FixedWidthValue;
use Kint\Value\UninitializedValue;
use PHPUnit\Framework\Error\Warning;
use ReflectionClass;
use ReflectionProperty;

/**
 * @coversNothing
 */
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
        $parser = $this->createMock(Parser::class);
        $renderer = $this->createMock(TextRenderer::class);
        $k = new Kint($parser, $renderer);

        $p1 = new TimestampPlugin($parser);
        $p2 = new TimestampPlugin($parser);
        $p3 = new TimestampPlugin($parser);
        $p4 = new TimestampPlugin($parser);

        $statics = [
            'expanded' => true,
            'return' => true,
            'display_called_from' => true,
            'depth_limit' => 42,
            'plugins' => [$p1, $p2, $p3, $p4],
        ];

        $parser->expects($this->once())->method('setDepthLimit')->with(42);
        $parser->expects($this->once())->method('clearPlugins');

        $expected_plugins = [$p1, $p3, $p4];

        $renderer->expects($this->once())->method('setStatics')->with($this->identicalTo($statics));
        $renderer->expects($this->once())
            ->method('filterParserPlugins')
            ->with($this->identicalTo($statics['plugins']))
            ->willReturn($expected_plugins);

        $parser->expects($this->exactly(3))
            ->method('addPlugin')
            ->willReturnCallback(function ($plugin) use (&$expected_plugins) {
                $index = \array_search($plugin, $expected_plugins, true);
                if (false === $index) {
                    $this->fail('Unexpected plugin added to parser');
                }
                unset($expected_plugins[$index]);

                return true;
            });

        $k->setStatesFromStatics($statics);

        $this->assertCount(0, $expected_plugins);
    }

    /**
     * @covers \Kint\Kint::setStatesFromStatics
     */
    public function testSetStatesFromStaticsStringPlugins()
    {
        $parser = $this->createMock(Parser::class);
        $renderer = $this->createMock(TextRenderer::class);
        $k = new Kint($parser, $renderer);

        $statics = [
            'plugins' => [
                TimestampPlugin::class,
                MicrotimePlugin::class,
            ],
        ];

        $parser->expects($this->once())->method('setDepthLimit')->with(0);
        $parser->expects($this->once())->method('clearPlugins');

        $renderer->expects($this->once())->method('setStatics')->with($statics);
        $renderer->expects($this->once())
            ->method('filterParserPlugins')
            ->willReturnCallback(function ($plugins) {
                $out = [];

                foreach ($plugins as $plugin) {
                    if ($plugin instanceof TimestampPlugin) {
                        $out[] = $plugin;
                    } elseif (!$plugin instanceof ConstructablePluginInterface) {
                        $this->fail('Invalid plugin passed to filterParserPlugins');
                    }
                }

                return $out;
            });

        $parser->expects($this->once())->method('addPlugin')->willReturnCallback(function ($plugin) use (&$expected_plugins) {
            if (!$plugin instanceof TimestampPlugin) {
                $this->fail('Unexpected plugin added to parser');
            }

            return true;
        });

        $k->setStatesFromStatics($statics);
    }

    /**
     * @covers \Kint\Kint::setStatesFromStatics
     */
    public function testSetStatesFromStaticsEmpty()
    {
        $parser = $this->createMock(Parser::class);
        $renderer = $this->createMock(TextRenderer::class);
        $k = new Kint($parser, $renderer);

        $renderer->expects($this->once())->method('setStatics')->with([]);

        $parser->expects($this->once())->method('setDepthLimit')->with(0);
        $parser->expects($this->once())->method('clearPlugins');

        $k->setStatesFromStatics([]);
    }

    /**
     * @covers \Kint\Kint::setStatesFromStatics
     */
    public function testSetStatesFromStaticsBadPlugin()
    {
        $parser = new Parser();
        $renderer = $this->createMock(TextRenderer::class);
        $renderer->expects($this->once())
            ->method('filterParserPlugins')
            ->willReturnCallback(fn ($in) => $in);
        $k = new Kint($parser, $renderer);

        $statics = [
            'plugins' => [
                new class($parser) extends AbstractPlugin {
                    public function getTypes(): array
                    {
                        return ['int'];
                    }

                    public function getTriggers(): int
                    {
                        return Parser::TRIGGER_COMPLETE;
                    }
                },
            ],
        ];

        $k->setStatesFromStatics([]);

        try {
            $k->setStatesFromStatics($statics);
        } catch (Warning $w) {
            if (KINT_PHP82) {
                $this->assertStringStartsWith('Plugin '.AbstractPlugin::class."@anonymous\0/", $w->getMessage());
            } elseif (KINT_PHP80) {
                $this->assertStringStartsWith('Plugin '.AbstractPlugin::class.'@anonymous ', $w->getMessage());
            } else {
                $this->assertStringStartsWith('Plugin class@anonymous ', $w->getMessage());
            }

            return;
        }

        $this->fail("Didn't get warning");
    }

    /**
     * @covers \Kint\Kint::setStatesFromCallInfo
     */
    public function testSetStatesFromCallInfo()
    {
        $ci1 = ['foo' => 'bar'];
        $ci2 = [
            'modifiers' => ['!', '@', '+'],
            'caller' => [
                'class' => 'test1234',
            ],
        ];

        $r = $this->createMock(TextRenderer::class);
        $r->expects($count = $this->exactly(2))
            ->method('setCallInfo')
            ->willReturnCallback(function ($param) use ($count, $ci1, $ci2) {
                switch ($count->getInvocationCount()) {
                    case 1:
                        $this->assertSame($ci1, $param);

                        return;
                    case 2:
                        $this->assertSame($ci2, $param);

                        return;
                }
            });

        $p = new Parser(42);
        $k = new Kint($p, $r);

        $k->setStatesFromCallInfo($ci1);

        $this->assertSame(42, $p->getDepthLimit());
        $this->assertNull($p->getCallerClass());

        $k->setStatesFromCallInfo($ci2);

        $this->assertSame(0, $p->getDepthLimit());
        $this->assertSame('test1234', $p->getCallerClass());
    }

    /**
     * @covers \Kint\Kint::dumpAll
     * @covers \Kint\Kint::dumpVar
     */
    public function testDumpAll()
    {
        $parser = $this->createMock(Parser::class);
        $renderer = $this->createMock(TextRenderer::class);
        $k = new Kint($parser, $renderer);

        $v = 123;
        $base = new BaseContext('$v');
        $val = new FixedWidthValue($base, $v);

        $parser->expects($this->exactly(2))->method('parse')->with($v, $this->identicalTo($base))->willReturn($val);

        $renderer->expects($this->once())->method('preRender')->willReturn('pre.');
        $renderer->expects($this->exactly(2))->method('render')->with($this->identicalTo($val))->willReturn('render.');
        $renderer->expects($this->once())->method('postRender')->willReturn('post');

        $this->assertSame('pre.render.render.post', $k->dumpAll([$v, $v], [$base, $base]));
    }

    /**
     * @covers \Kint\Kint::dumpAll
     */
    public function testDumpNothing()
    {
        $parser = $this->createMock(Parser::class);
        $renderer = $this->createMock(TextRenderer::class);
        $k = new Kint($parser, $renderer);

        $renderer->expects($this->once())->method('preRender')->willReturn('pre.');
        $renderer->expects($this->once())->method('render')->with($this->equalTo(new UninitializedValue(new BaseContext('No argument'))))->willReturn('nothing.');
        $renderer->expects($this->once())->method('postRender')->willReturn('post');

        $parser->expects($this->never())->method('parse');

        $this->assertSame('pre.nothing.post', $k->dumpAll([], []));
    }

    /**
     * @covers \Kint\Kint::dumpAll
     */
    public function testDumpAllUnmatchingArgs()
    {
        $this->expectException(InvalidArgumentException::class);

        $p = new Parser();
        $r = new TextRenderer();
        $k = new Kint($p, $r);

        $k->dumpAll([$k], [new BaseContext('$k'), 'bar' => 'baz']);
    }

    /**
     * @covers \Kint\Kint::dumpAll
     */
    public function testDumpAllIncorrectBase()
    {
        $this->expectException(InvalidArgumentException::class);

        $p = new Parser();
        $r = new TextRenderer();
        $k = new Kint($p, $r);

        $k->dumpAll([$k], ['foo']);
    }

    /**
     * @covers \Kint\Kint::getStatics
     */
    public function testGetStatics()
    {
        $r = new ReflectionClass('Kint');

        $props = $r->getProperties(ReflectionProperty::IS_STATIC);
        $props_array = [];
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
        return [
            'no options' => [
                [],
                false,
            ],
            'auto without cli' => [
                [
                    'enabled_mode' => true,
                    'mode_default' => 42,
                    'cli_detection' => false,
                    'mode_default_cli' => 43,
                    'renderers' => [
                        42 => RichRenderer::class,
                        43 => CliRenderer::class,
                        44 => PlainRenderer::class,
                    ],
                ],
                RichRenderer::class,
            ],
            'auto with cli' => [
                [
                    'enabled_mode' => true,
                    'mode_default' => 42,
                    'cli_detection' => true,
                    'mode_default_cli' => 43,
                    'renderers' => [
                        42 => RichRenderer::class,
                        43 => CliRenderer::class,
                        44 => PlainRenderer::class,
                    ],
                ],
                CliRenderer::class,
            ],
            'specific' => [
                [
                    'enabled_mode' => 44,
                    'mode_default' => 42,
                    'cli_detection' => true,
                    'mode_default_cli' => 43,
                    'renderers' => [
                        42 => RichRenderer::class,
                        43 => CliRenderer::class,
                        44 => PlainRenderer::class,
                    ],
                ],
                PlainRenderer::class,
            ],
            'disabled' => [
                ['enabled_mode' => false],
                false,
            ],
            'missing renderer' => [
                [
                    'enabled_mode' => 45,
                    'mode_default' => 42,
                    'cli_detection' => true,
                    'mode_default_cli' => 43,
                    'renderers' => [
                        42 => RichRenderer::class,
                        43 => CliRenderer::class,
                        44 => PlainRenderer::class,
                    ],
                ],
                TextRenderer::class,
            ],
            'falsey renderer' => [
                [
                    'enabled_mode' => true,
                    'mode_default' => 0,
                    'cli_detection' => false,
                    'mode_default_cli' => 1,
                    'renderers' => [
                        0 => RichRenderer::class,
                        1 => CliRenderer::class,
                        2 => PlainRenderer::class,
                    ],
                ],
                RichRenderer::class,
            ],
            'instanced rendererer' => [
                [
                    'enabled_mode' => true,
                    'mode_default' => 42,
                    'cli_detection' => false,
                    'mode_default_cli' => 43,
                    'renderers' => [
                        42 => new RichRenderer(),
                        43 => CliRenderer::class,
                        44 => PlainRenderer::class,
                    ],
                ],
                RichRenderer::class,
            ],
        ];
    }

    /**
     * @covers \Kint\Kint::createFromStatics
     *
     * @dataProvider staticModeProvider
     *
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
        $baseinfo = [
            'normal params' => [
                [
                    [
                        'name' => '$a',
                        'path' => '$a',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => '$b[...]',
                        'path' => '$b[$a]',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => '[...]',
                        'path' => '[$a, $b]',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                ],
                3,
                [
                    ['$a', '$a'],
                    ['$b[...]', '$b[$a]'],
                    ['[...]', '[$a, $b]'],
                ],
            ],
            'expressions params' => [
                [
                    [
                        'name' => '$a + $b',
                        'path' => '$a + $b',
                        'expression' => true,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => '[...] + $c[...]',
                        'path' => '[$a, $b] + $c[$d]',
                        'expression' => true,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                ],
                2,
                [
                    ['$a + $b', '($a + $b)'],
                    ['[...] + $c[...]', '([$a, $b] + $c[$d])'],
                ],
            ],
            'new without parens params' => [
                [
                    [
                        'name' => 'new X',
                        'path' => 'new X',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => true,
                    ],
                    [
                        'name' => 'new (...)',
                        'path' => 'new ("X")',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => true,
                    ],
                ],
                2,
                [
                    ['new X', 'new X()'],
                    ['new (...)', 'new ("X")()'],
                ],
            ],
            'missing params' => [
                [],
                2,
                [
                    ['$0', '$0'],
                    ['$1', '$1'],
                ],
            ],
            'no params' => [
                [],
                0,
                [],
            ],
            'renamed literals' => [
                [
                    [
                        'name' => 'null',
                        'path' => 'null',
                        'expression' => false,
                        'literal' => true,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => 'true',
                        'path' => 'true',
                        'expression' => false,
                        'literal' => true,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => 'false',
                        'path' => 'false',
                        'expression' => false,
                        'literal' => true,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => '"..."',
                        'path' => '"Hello world"',
                        'expression' => false,
                        'literal' => true,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => '1234.5678',
                        'path' => '1234.5678',
                        'expression' => false,
                        'literal' => true,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => '[]',
                        'path' => '[]',
                        'expression' => false,
                        'literal' => true,
                        'new_without_parens' => false,
                    ],
                ],
                6,
                [
                    ['literal', 'null'],
                    ['literal', 'true'],
                    ['literal', 'false'],
                    ['literal', '"Hello world"'],
                    ['literal', '1234.5678'],
                    ['literal', '[]'],
                ],
            ],
        ];

        foreach ($baseinfo as &$run) {
            foreach ($run[2] as &$expected) {
                $base = new BaseContext($expected[0]);
                $base->access_path = $expected[1];
                $expected = $base;
            }
        }

        return $baseinfo;
    }

    /**
     * @covers \Kint\Kint::getBasesFromParamInfo
     *
     * @dataProvider baseProvider
     *
     * @param int $count
     */
    public function testGetBasesFromParamInfo(array $paraminfo, $count, array $expect)
    {
        $bases = Kint::getBasesFromParamInfo($paraminfo, $count);

        $this->assertEquals($expect, $bases);
    }

    public function getCallInfoProvider()
    {
        $aliases = [
            ['kint', 'dump'],
            'd',
            's',
        ];

        $basetrace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $basetrace[0]['file'] = __FILE__;
        $basetrace[0]['line'] = __LINE__;
        // Apparently DEBUG_BACKTRACE_IGNORE_ARGS isn't enough
        foreach ($basetrace as $index => $frame) {
            unset($basetrace[$index]['args']);
        }

        $dumpframe = [
            'class' => 'Kint',
            'function' => 'dump',
        ];

        $data['empty trace'] = [
            'aliases' => $aliases,
            'trace' => [],
            'args' => \range(0, 1234),
            'expect' => [
                'params' => null,
                'modifiers' => [],
                'callee' => null,
                'caller' => null,
                'trace' => [],
            ],
        ];

        $data['full trace'] = [
            'aliases' => $aliases,
            'trace' => [
                ...$basetrace,
                $dumpframe + [
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE,
                ],
                ['function' => 'usort'],
                ...$basetrace,
            ],
            'args' => \range(0, 1234),
            'expect' => [
                'params' => null,
                'modifiers' => [],
                'callee' => $dumpframe + [
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE,
                ],
                'caller' => [
                    'function' => 'usort',
                ],
                'trace' => [
                    $dumpframe + [
                        'file' => TestClass::DUMP_FILE,
                        'line' => TestClass::DUMP_LINE,
                    ],
                    ...$basetrace,
                ],
            ],
        ];

        $data['internal callee trace'] = $data['full trace'];
        $data['internal callee trace']['aliases'][] = 'usort';
        $data['internal callee trace']['expect']['callee'] = ['function' => 'usort'];
        $data['internal callee trace']['expect']['caller'] = $basetrace[0];
        $data['internal callee trace']['expect']['trace'] = [['function' => 'usort'], ...$basetrace];
        $data['internal callee trace']['trace'] = $data['internal callee trace']['expect']['trace'];

        $data['unmatching trace'] = $data['full trace'];
        $data['unmatching trace']['aliases'] = [];
        $data['unmatching trace']['expect']['callee'] = null;
        $data['unmatching trace']['expect']['caller'] = null;
        $data['unmatching trace']['expect']['trace'] = [
            ...$basetrace,
            $dumpframe + [
                'file' => TestClass::DUMP_FILE,
                'line' => TestClass::DUMP_LINE,
            ],
            ...$basetrace,
        ];

        $data['trace with params'] = $data['full trace'];
        $data['trace with params']['args'] = [1, 2, 3];
        $data['trace with params']['expect']['params'] = [
            ['name' => '$x', 'path' => '$x', 'expression' => false, 'literal' => false, 'new_without_parens' => false],
            ['name' => '$y', 'path' => '$y', 'expression' => false, 'literal' => false, 'new_without_parens' => false],
            ['name' => '$z', 'path' => '$z', 'expression' => false, 'literal' => false, 'new_without_parens' => false],
        ];

        $data['trace with modifiers'] = [
            'aliases' => $aliases,
            'trace' => [
                $dumpframe + [
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 1,
                ],
                ...$basetrace,
            ],
            'args' => [],
            'expect' => [
                'params' => [],
                'modifiers' => ['!', '+'],
                'callee' => $dumpframe + [
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 1,
                ],
                'caller' => $basetrace[0],
                'trace' => [
                    $dumpframe + [
                        'file' => TestClass::DUMP_FILE,
                        'line' => TestClass::DUMP_LINE + 1,
                    ],
                    ...$basetrace,
                ],
            ],
        ];

        $data['trace function with modifier'] = [
            'aliases' => $aliases,
            'trace' => [
                [
                    'function' => 'd',
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 2,
                ],
                ...$basetrace,
            ],
            'args' => [1],
            'expect' => [
                'params' => [
                    [
                        'name' => '$x',
                        'path' => '$x',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                ],
                'modifiers' => ['~'],
                'callee' => [
                    'function' => 'd',
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 2,
                ],
                'caller' => $basetrace[0],
                'trace' => [
                    [
                        'function' => 'd',
                        'file' => TestClass::DUMP_FILE,
                        'line' => TestClass::DUMP_LINE + 2,
                    ],
                    ...$basetrace,
                ],
            ],
        ];

        $data['missing args somehow'] = [
            'aliases' => $aliases,
            'trace' => [
                [
                    'function' => 'd',
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 2,
                ],
                ...$basetrace,
            ],
            'args' => [],
            'expect' => [
                'params' => null,
                'modifiers' => [],
                'callee' => [
                    'function' => 'd',
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 2,
                ],
                'caller' => $basetrace[0],
                'trace' => [
                    [
                        'function' => 'd',
                        'file' => TestClass::DUMP_FILE,
                        'line' => TestClass::DUMP_LINE + 2,
                    ],
                    ...$basetrace,
                ],
            ],
        ];

        $data['trace function with multiple hits'] = [
            'aliases' => $aliases,
            'trace' => [
                [
                    'function' => 'd',
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 3,
                ],
                ...$basetrace,
            ],
            'args' => [1],
            'expect' => [
                'params' => null,
                'modifiers' => [],
                'callee' => [
                    'function' => 'd',
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 3,
                ],
                'caller' => $basetrace[0],
                'trace' => [
                    [
                        'function' => 'd',
                        'file' => TestClass::DUMP_FILE,
                        'line' => TestClass::DUMP_LINE + 3,
                    ],
                    ...$basetrace,
                ],
            ],
        ];

        $data['trace with unpack'] = [
            'aliases' => $aliases,
            'trace' => [
                $dumpframe + [
                    'file' => Php56TestClass::DUMP_FILE,
                    'line' => Php56TestClass::DUMP_LINE,
                ],
                ...$basetrace,
            ],
            'args' => [1, 2, 3, 4],
            'expect' => [
                'params' => [
                    [
                        'name' => '$x',
                        'path' => '$x',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => '$y',
                        'path' => '$y',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => 'array_values($z)[0]',
                        'path' => 'array_values($z)[0]',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => 'array_values($z)[1]',
                        'path' => 'array_values($z)[1]',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                ],
                'modifiers' => [],
                'callee' => $dumpframe + [
                    'file' => Php56TestClass::DUMP_FILE,
                    'line' => Php56TestClass::DUMP_LINE,
                ],
                'caller' => $basetrace[0],
                'trace' => [
                    $dumpframe + [
                        'file' => Php56TestClass::DUMP_FILE,
                        'line' => Php56TestClass::DUMP_LINE,
                    ],
                    ...$basetrace,
                ],
            ],
        ];

        $data['trace with empty unpack'] = [
            'aliases' => $aliases,
            'trace' => [
                $dumpframe + [
                    'file' => Php56TestClass::DUMP_FILE,
                    'line' => Php56TestClass::DUMP_LINE,
                ],
                ...$basetrace,
            ],
            'args' => [1, 2],
            'expect' => [
                'params' => [
                    [
                        'name' => '$x',
                        'path' => '$x',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                    [
                        'name' => '$y',
                        'path' => '$y',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                ],
                'modifiers' => [],
                'callee' => $dumpframe + [
                    'file' => Php56TestClass::DUMP_FILE,
                    'line' => Php56TestClass::DUMP_LINE,
                ],
                'caller' => $basetrace[0],
                'trace' => [
                    $dumpframe + [
                        'file' => Php56TestClass::DUMP_FILE,
                        'line' => Php56TestClass::DUMP_LINE,
                    ],
                    ...$basetrace,
                ],
            ],
        ];

        $data['trace with double unpack'] = [
            'aliases' => $aliases,
            'trace' => [
                $dumpframe + [
                    'file' => Php56TestClass::DUMP_FILE,
                    'line' => Php56TestClass::DUMP_LINE + 1,
                ],
                ...$basetrace,
            ],
            'args' => \range(0, 9),
            'expect' => [
                'params' => [],
                'modifiers' => [],
                'callee' => $dumpframe + [
                    'file' => Php56TestClass::DUMP_FILE,
                    'line' => Php56TestClass::DUMP_LINE + 1,
                ],
                'caller' => $basetrace[0],
                'trace' => [
                    $dumpframe + [
                        'file' => Php56TestClass::DUMP_FILE,
                        'line' => Php56TestClass::DUMP_LINE + 1,
                    ],
                    ...$basetrace,
                ],
            ],
        ];

        $data['multiple trace with unpack one match'] = [
            'aliases' => $aliases,
            'trace' => [
                $dumpframe + [
                    'file' => Php56TestClass::DUMP_FILE,
                    'line' => Php56TestClass::DUMP_LINE + 2,
                ],
                ...$basetrace,
            ],
            'args' => [1],
            'expect' => [
                'params' => [
                    [
                        'name' => '$x',
                        'path' => '$x',
                        'expression' => false,
                        'literal' => false,
                        'new_without_parens' => false,
                    ],
                ],
                'modifiers' => [],
                'callee' => $dumpframe + [
                    'file' => Php56TestClass::DUMP_FILE,
                    'line' => Php56TestClass::DUMP_LINE + 2,
                ],
                'caller' => $basetrace[0],
                'trace' => [
                    $dumpframe + [
                        'file' => Php56TestClass::DUMP_FILE,
                        'line' => Php56TestClass::DUMP_LINE + 2,
                    ],
                    ...$basetrace,
                ],
            ],
        ];

        $data['multiple trace with unpack multiple match'] = $data['multiple trace with unpack one match'];
        $data['multiple trace with unpack multiple match']['args'] = [1, 2];
        $data['multiple trace with unpack multiple match']['expect']['params'] = null;

        $data['multiple trace with unpack no match'] = $data['multiple trace with unpack one match'];
        $data['multiple trace with unpack no match']['args'] = [];
        $data['multiple trace with unpack no match']['expect']['params'] = null;

        $data['trace with associated unpack'] = $data['trace with unpack'];
        $data['trace with associated unpack']['args'] = [1, 2, 'a' => 3, 'b' => 4];
        $data['trace with associated unpack']['expect']['params'] = [
            [
                'name' => '$x',
                'path' => '$x',
                'expression' => false,
                'literal' => false,
                'new_without_parens' => false,
            ],
            [
                'name' => '$y',
                'path' => '$y',
                'expression' => false,
                'literal' => false,
                'new_without_parens' => false,
            ],
            [
                'name' => '$z[\'a\']',
                'path' => '$z[\'a\']',
                'expression' => false,
                'literal' => false,
                'new_without_parens' => false,
            ],
            [
                'name' => '$z[\'b\']',
                'path' => '$z[\'b\']',
                'expression' => false,
                'literal' => false,
                'new_without_parens' => false,
            ],
        ];

        $data['trace with double associated unpack'] = $data['trace with double unpack'];
        $data['trace with double associated unpack']['args'] = ['a' => 3, 'b' => 4, 'c' => 5];

        return $data;
    }

    /**
     * @dataProvider getCallInfoProvider
     *
     * @covers \Kint\Kint::getCallInfo
     * @covers \Kint\Kint::getSingleCall
     *
     * @param array $aliases
     * @param array $trace
     * @param array $args
     * @param array $expect
     */
    public function testGetCallInfo($aliases, $trace, $args, $expect)
    {
        $this->assertSame($expect, Kint::getCallInfo($aliases, $trace, $args));
    }
}
