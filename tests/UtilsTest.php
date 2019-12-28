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

use Kint\Utils;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionParameter;

class UtilsTest extends TestCase
{
    protected $composer_stash;
    protected $installed_stash;
    protected $composer_test_dir;

    protected function setUp()
    {
        parent::setUp();

        if (\getenv('KINT_FILE')) {
            $this->composer_test_dir = \dirname(__DIR__);
        } else {
            $this->composer_test_dir = KINT_DIR;
        }

        $this->composer_stash = \file_get_contents($this->composer_test_dir.'/composer.json');
        $this->installed_stash = \file_get_contents($this->composer_test_dir.'/vendor/composer/installed.json');
    }

    protected function tearDown()
    {
        parent::tearDown();

        if ($this->composer_stash) {
            \file_put_contents($this->composer_test_dir.'/composer.json', $this->composer_stash);
            \file_put_contents($this->composer_test_dir.'/vendor/composer/installed.json', $this->installed_stash);
            $this->composer_stash = null;
            $this->installed_stash = null;
            if (\file_exists($this->composer_test_dir.'/composer/installed.json')) {
                \unlink($this->composer_test_dir.'/composer/installed.json');
            }
            if (\file_exists($this->composer_test_dir.'/composer')) {
                \rmdir($this->composer_test_dir.'/composer');
            }
        }
    }

    public function testConstruct()
    {
        $u = new ReflectionMethod('Kint\\Utils', '__construct');
        $this->assertTrue($u->isPrivate());
    }

    public function humanReadableBytesProvider()
    {
        return [
            '-1 byte' => [
                -1,
                [
                    'value' => -1.0,
                    'unit' => 'B',
                ],
            ],
            '0 bytes' => [
                0,
                [
                    'value' => 0.0,
                    'unit' => 'B',
                ],
            ],
            '1 byte' => [
                1,
                [
                    'value' => 1.0,
                    'unit' => 'B',
                ],
            ],
            '<1 k' => [
                1023,
                [
                    'value' => 1023.0,
                    'unit' => 'B',
                ],
            ],
            '1 k' => [
                1024,
                [
                    'value' => 1.0,
                    'unit' => 'KB',
                ],
            ],
            '<1 meg' => [
                \pow(2, 20) - 1,
                [
                    'value' => 1.0,
                    'unit' => 'MB',
                ],
            ],
            '1 meg' => [
                \pow(2, 20),
                [
                    'value' => 1.0,
                    'unit' => 'MB',
                ],
            ],
            '<1 gig' => [
                \pow(2, 30) - 1,
                [
                    'value' => 1.0,
                    'unit' => 'GB',
                ],
            ],
            '1 gig' => [
                \pow(2, 30),
                [
                    'value' => 1.0,
                    'unit' => 'GB',
                ],
            ],
            '<1 tig' => [
                \pow(2, 40) - 1,
                [
                    'value' => 1.0,
                    'unit' => 'TB',
                ],
            ],
            '1 tig' => [
                \pow(2, 40),
                [
                    'value' => 1.0,
                    'unit' => 'TB',
                ],
            ],
            '>1 tig' => [
                \pow(2, 50),
                [
                    'value' => 1024.0,
                    'unit' => 'TB',
                ],
            ],
            'halfway' => [
                15000,
                [
                    'value' => \round(15000 / 1024, 1),
                    'unit' => 'KB',
                ],
            ],
        ];
    }

    /**
     * @covers \Kint\Utils::getHumanReadableBytes
     * @dataProvider humanReadableBytesProvider
     *
     * @param int   $input
     * @param array $expect
     */
    public function testGetHumanReadableBytes($input, $expect)
    {
        $this->assertSame($expect, Utils::getHumanReadableBytes($input));
    }

    public function sequentialArrayProvider()
    {
        return [
            'sequential array' => [
                [1, 2, 3],
                true,
            ],
            'explicit sequential array' => [
                [0 => 1, 1 => 2, 2 => 3],
                true,
            ],
            'arrays start at 1' => [
                [1 => 1, 2 => 2, 3 => 3],
                false,
            ],
            'wrong order' => [
                [0 => 1, 2 => 2, 1 => 3],
                false,
            ],
            'string keys' => [
                [0 => 1, 1 => 2, 'two' => 3],
                false,
            ],
            'string int keys' => [
                ['0' => 1, '1' => 2, '2' => 3],
                true,
            ],
            'padded string int keys' => [
                ['00' => 1, '01' => 2, '02' => 3],
                false,
            ],
        ];
    }

    /**
     * @covers \Kint\Utils::isSequential
     * @dataProvider sequentialArrayProvider
     *
     * @param array $input
     * @param bool  $expect
     */
    public function testIsSequential($input, $expect)
    {
        $this->assertSame($expect, Utils::isSequential($input));
    }

    /**
     * This is a flimsy test but it's as good as it gets without altering
     * composer.json mid-test without a proper setup/teardown in place.
     *
     * @covers \Kint\Utils::composerGetExtras
     */
    public function testComposerGetExtras()
    {
        \file_put_contents($this->composer_test_dir.'/composer.json', \json_encode([
            'extra' => [
                'kint' => ['test' => 'data'],
            ],
        ]));

        if (\getenv('KINT_FILE')) {
            $this->assertSame([], Utils::composerGetExtras('kint'));

            return;
        }

        $this->assertSame(['test' => 'data'], Utils::composerGetExtras('kint'));

        \mkdir($this->composer_test_dir.'/composer');
        \unlink($this->composer_test_dir.'/vendor/composer/installed.json');

        \file_put_contents($this->composer_test_dir.'/composer/installed.json', \json_encode([
            [
                'extra' => [
                    'kint' => ['more' => 'test', 'data'],
                ],
            ],
            [
                'extra' => [
                    'kint' => ['test' => 'ing'],
                ],
            ],
        ]));

        $this->assertSame(['more' => 'test', 'data', 'test' => 'ing'], Utils::composerGetExtras('kint'));
    }

    public function traceProvider()
    {
        $bt = \debug_backtrace(true);
        $bad_bt_1 = $bt;
        $bad_bt_1[0]['test'] = 'woot';
        $bad_bt_2 = $bt;
        $bad_bt_2[0]['function'] = 1234;

        return [
            'empty' => [
                'trace' => [],
                'expect' => false,
            ],
            'backtrace' => [
                'trace' => $bt,
                'expect' => true,
            ],
            'bad backtrace, extra key' => [
                'trace' => $bad_bt_1,
                'expect' => false,
            ],
            'bad backtrace, wrong type' => [
                'trace' => $bad_bt_2,
                'expect' => false,
            ],
            'mythical' => [
                'trace' => [
                    [
                        'function' => 'mythical_internal_function_with_no_args_that_results_in_a_backtrace',
                        'file' => __FILE__,
                        'line' => 1,
                    ],
                ],
                'expect' => true,
            ],
            'normal array' => [
                'trace' => [1, 2, 3],
                'expect' => false,
            ],
        ];
    }

    /**
     * @dataProvider traceProvider
     * @covers \Kint\Utils::isTrace
     *
     * @param bool $expected
     */
    public function testIsTrace(array $trace, $expected)
    {
        $this->assertSame($expected, Utils::isTrace($trace));
    }

    public function frameProvider()
    {
        return [
            'function match' => [
                'frame' => [
                    'function' => 'testAWeirdFunctionName',
                ],
                'matches' => ['testaweirdfunctionname'],
                'expected' => true,
            ],
            'function no match denormalized' => [
                'frame' => [
                    'function' => 'testAWeirdFunctionName',
                ],
                'matches' => ['testAWeirdFunctionName'],
                'expected' => false,
            ],
            'function no match method' => [
                'frame' => [
                    'function' => 'testAWeirdFunctionName',
                ],
                'matches' => [['test', 'testaweirdfunctionname']],
                'expected' => false,
            ],
            'method no match function' => [
                'frame' => [
                    'function' => 'testAWeirdFunctionName',
                    'class' => 'test',
                ],
                'matches' => ['testAWeirdFunctionName'],
                'expected' => false,
            ],
            'method match' => [
                'frame' => [
                    'function' => 'testAWeirdFunctionName',
                    'class' => 'test',
                ],
                'matches' => [['test', 'testaweirdfunctionname']],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider frameProvider
     * @covers \Kint\Utils::traceFrameIsListed
     *
     * @param bool $expected
     */
    public function testTraceFrameIsListed(array $frame, array $matches, $expected)
    {
        $this->assertSame($expected, Utils::traceFrameIsListed($frame, $matches));
    }

    /**
     * @covers \Kint\Utils::normalizeAliases
     */
    public function testNormalizeAliases()
    {
        $input = [
            'CamelCaseFunction',
            'snake_case_function',
            'One_of_the_FunctionsMyColleaguesMADE__',
            'stringThatCan\'tBeAfunction',
            'another string that can not be a function',
            '\\NameSpaced\\Function',
            ['clASs', 'meThod'],
            [$this, 'meThod'],
            ['a', 'b', 'c'],
            ['\\big\\long\\class\\name', 'method'],
        ];

        $expected = [
            'camelcasefunction',
            'snake_case_function',
            'one_of_the_functionsmycolleaguesmade__',
            'function',
            ['class', 'method'],
            ['big\\long\\class\\name', 'method'],
        ];

        Utils::normalizeAliases($input);

        $this->assertSame($expected, $input);
    }

    public function truncateStringProvider()
    {
        return [
            'standard string' => [
                'input' => '123456789',
                'expect' => '123...',
                'length' => 6,
            ],
            'overclip' => [
                'input' => '123456789',
                'expect' => '12345...',
                'length' => 8,
            ],
            'correct length' => [
                'input' => '123456789',
                'expect' => '123456789',
                'length' => 9,
            ],
            'over length' => [
                'input' => '123456789',
                'expect' => '123456789',
                'length' => 100,
            ],
        ];
    }

    /**
     * @dataProvider truncateStringProvider
     * @covers \Kint\Utils::truncateString
     *
     * @param string $input
     * @param string $expect
     * @param int    $length
     */
    public function testTruncateString($input, $expect, $length)
    {
        $this->assertSame($expect, Utils::truncateString($input, $length));
    }

    /**
     * @covers \Kint\Utils::truncateString
     * @expectedException \InvalidArgumentException
     */
    public function testTruncateStringException()
    {
        Utils::truncateString('asdf', 1);
    }

    /**
     * @covers \Kint\Utils::getTypeString
     */
    public function testGetTypeString()
    {
        if (!KINT_PHP70) {
            $this->markTestSkipped('Not testing PHP7+ parameter type hints on PHP5');
        }

        $param = new ReflectionParameter(['Kint\\Test\\Fixtures\\TestClass', 'arrayHint'], 'x');

        $this->assertSame('array', Utils::getTypeString($param->getType()));
    }
}
