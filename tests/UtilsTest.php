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

use Kint\Test\Fixtures\Php71TestClass;
use Kint\Test\Fixtures\Php81TestClass;
use Kint\Test\Fixtures\Php8TestClass;
use Kint\Test\Fixtures\TestClass;
use Kint\Utils;
use ReflectionMethod;
use ReflectionParameter;

/**
 * @coversNothing
 */
class UtilsTest extends KintTestCase
{
    protected $composer_stash;
    protected $installed_stash;
    protected $composer_test_dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composer_test_dir = \dirname(__DIR__);
        $this->composer_stash = \file_get_contents($this->composer_test_dir.'/composer.json');
        $this->installed_stash = \file_get_contents($this->composer_test_dir.'/vendor/composer/installed.json');
    }

    protected function tearDown(): void
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
        $u = new ReflectionMethod(Utils::class, '__construct');
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
     *
     * @dataProvider humanReadableBytesProvider
     *
     * @param int   $input
     * @param array $expect
     */
    public function testGetHumanReadableBytes($input, $expect)
    {
        $this->assertSame($expect, Utils::getHumanReadableBytes($input));
    }

    public function arrayProvider()
    {
        return [
            'sequential array' => [
                [1, 2, 3],
                true,
                false,
            ],
            'explicit sequential array' => [
                [0 => 1, 1 => 2, 2 => 3],
                true,
                false,
            ],
            'arrays start at 1' => [
                [1 => 1, 2 => 2, 3 => 3],
                false,
                false,
            ],
            'wrong order' => [
                [0 => 1, 2 => 2, 1 => 3],
                false,
                false,
            ],
            'string keys' => [
                [0 => 1, 1 => 2, 'two' => 3],
                false,
                true,
            ],
            'string int keys' => [
                ['0' => 1, '1' => 2, '2' => 3],
                true,
                false,
            ],
            'padded string int keys' => [
                ['00' => 1, '01' => 2, '02' => 3],
                false,
                true,
            ],
        ];
    }

    /**
     * @covers \Kint\Utils::isSequential
     *
     * @dataProvider arrayProvider
     *
     * @param array $input
     * @param bool  $seq
     */
    public function testIsSequential($input, $seq)
    {
        $this->assertSame($seq, Utils::isSequential($input));
    }

    /**
     * @covers \Kint\Utils::isAssoc
     *
     * @dataProvider arrayProvider
     *
     * @param array $input
     * @param bool  $_
     * @param bool  $assoc
     */
    public function testIsAssoc($input, $_, $assoc)
    {
        $this->assertSame($assoc, Utils::isAssoc($input));

        $input['key'] = 'val';

        $this->assertTrue(Utils::isAssoc($input));
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

        if (\getenv('KINT_PHAR_TEST')) {
            $this->assertSame([], Utils::composerGetExtras('kint'));

            return;
        }

        $this->assertSame(['test' => 'data'], Utils::composerGetExtras('kint'));

        \mkdir($this->composer_test_dir.'/composer');
        \unlink($this->composer_test_dir.'/vendor/composer/installed.json');

        $installed_contents = [
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
        ];

        // Composer 1 installed.json
        \file_put_contents($this->composer_test_dir.'/composer/installed.json', \json_encode($installed_contents));
        $this->assertSame(['more' => 'test', 'data', 'test' => 'ing'], Utils::composerGetExtras('kint'));

        // Composer 2 installed.json
        \file_put_contents($this->composer_test_dir.'/composer/installed.json', \json_encode(['packages' => $installed_contents]));
        $this->assertSame(['more' => 'test', 'data', 'test' => 'ing'], Utils::composerGetExtras('kint'));

        \file_put_contents($this->composer_test_dir.'/composer/installed.json', 'malformed JSON.');
        $this->assertSame([], Utils::composerGetExtras('kint'));
    }

    public function traceProvider()
    {
        $bt = \debug_backtrace();
        $bad_bt_1 = $bt;
        $bad_bt_1[0]['test'] = 'woot';
        $bad_bt_2 = $bt;
        $bad_bt_2[0]['function'] = 1234;
        $bad_bt_3 = $bt;
        $bad_bt_3[0]['class'] = 'woot_doesnt_exist';

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
            'bad backtrace, nonexistent class' => [
                'trace' => $bad_bt_3,
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
     *
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
     *
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

        $this->assertSame($expected, Utils::normalizeAliases($input));
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
            'under length' => [
                'input' => '123456',
                'expect' => '123',
                'length' => 3,
            ],
        ];
    }

    /**
     * @dataProvider truncateStringProvider
     *
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
     * @covers \Kint\Utils::getTypeString
     */
    public function testGetTypeString()
    {
        $param = new ReflectionParameter([TestClass::class, 'arrayHint'], 'x');
        $this->assertSame('array', Utils::getTypeString($param->getType()));

        $param = new ReflectionParameter([Php71TestClass::class, 'typeHints'], 'p1');
        $this->assertSame('?int', Utils::getTypeString($param->getType()));

        $param = new ReflectionParameter([Php71TestClass::class, 'typeHints'], 'nullable');
        $this->assertSame('?string', Utils::getTypeString($param->getType()));

        if (KINT_PHP80) {
            $param = new ReflectionParameter([Php8TestClass::class, 'typeHints'], 'p1');
            $this->assertSame('string|int', Utils::getTypeString($param->getType()));

            $param = new ReflectionParameter([Php8TestClass::class, 'typeHints'], 'mixed');
            $this->assertSame('mixed', Utils::getTypeString($param->getType()));
        }

        if (KINT_PHP81) {
            $param = new ReflectionParameter([Php81TestClass::class, 'typeHints'], 'p1');
            $this->assertSame('X&Y', Utils::getTypeString($param->getType()));
        }
    }

    public function phpNameProvider()
    {
        return [
            'valid name' => [
                'input' => 'asdf1234__asdf',
                'expect' => true,
            ],
            'invalid name starts with number' => [
                'input' => '1asdf1234__asdf',
                'expect' => false,
            ],
            'invalid name contains dash' => [
                'input' => 'asdf1234--asdf',
                'expect' => false,
            ],
            'invalid name empty' => [
                'input' => '',
                'expect' => false,
            ],
            'invalid name space' => [
                'input' => ' ',
                'expect' => false,
            ],
        ];
    }

    public function phpNamespaceProvider()
    {
        return [
            'valid namespace' => [
                'input' => 'asdf1234__asdf\\asdf1234__asdf\\asdf1234__asdf',
                'expect' => true,
            ],
            'valid namespace leading backslash' => [
                'input' => '\\asdf1234__asdf\\asdf1234__asdf\\asdf1234__asdf',
                'expect' => true,
            ],
            'invalid namespace part starts with number' => [
                'input' => 'asdf1234__asdf\\1asdf1234__asdf\\asdf1234__asdf',
                'expect' => false,
            ],
            'invalid namespace part contains dash' => [
                'input' => 'asdf1234__asdf\\asdf1234--asdf\\asdf1234__asdf',
                'expect' => false,
            ],
            'invalid namespace empty' => [
                'input' => '',
                'expect' => false,
            ],
            'invalid namespace space' => [
                'input' => ' ',
                'expect' => false,
            ],
        ];
    }

    /**
     * @dataProvider phpNameProvider
     *
     * @covers \Kint\Utils::isValidPhpName
     */
    public function testIsValidPhpName(string $input, bool $expect)
    {
        $this->assertSame($expect, Utils::isValidPhpName($input));
    }

    /**
     * @dataProvider phpNamespaceProvider
     *
     * @covers \Kint\Utils::isValidPhpNamespace
     */
    public function testIsValidPhpNamespace(string $input, bool $expect)
    {
        $this->assertSame($expect, Utils::isValidPhpNamespace($input));
    }

    /**
     * @covers \Kint\Utils::errorSanitizeString
     */
    public function testErrorSanitizeString()
    {
        $input = "Hello\0World";

        if (KINT_PHP82) {
            $this->assertSame($input, Utils::errorSanitizeString($input));
        } else {
            $this->assertSame('Hello', Utils::errorSanitizeString($input));
        }

        $this->assertSame('', Utils::errorSanitizeString(''));
    }

    public function blobProvider()
    {
        $encodings = [
            'ASCII',
            'UTF-8',
            'SJIS',
            'EUC-JP',
        ];

        $legacy = [
            'Windows-1252',
            'Windows-1251',
        ];

        $strings = [
            'empty' => [
                '',
                'ASCII',
            ],
            'ASCII' => [
                "The quick brown fox jumps<br>\r\n\tover the lazy dog",
                'ASCII',
            ],
            'UTF-8' => [
                "El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso",
                'UTF-8',
            ],
            'SJIS' => [
                \mb_convert_encoding("キント最強<br>\r\n\tASCII", 'SJIS', 'UTF-8'),
                'SJIS',
            ],
            'EUC-JP' => [
                \mb_convert_encoding("キント最強<br>\r\n\tASCII", 'EUC-JP', 'UTF-8'),
                'EUC-JP',
            ],
            'yuck' => [
                \mb_convert_encoding("El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso", 'Windows-1252', 'UTF-8'),
                'Windows-1252',
            ],
            'also yuck' => [
                \mb_convert_encoding('This here cyrillic привет Ќ', 'Windows-1251', 'UTF-8'),
                'Windows-1251',
            ],
            'fail' => [
                "The quick brown fox jumps<br>\r\n\tover the lazy dog\x90\x1b",
                false,
            ],
        ];

        foreach ($strings as $encoding => &$string) {
            $string[] = $encodings;
            $string[] = $legacy;
        }

        return $strings;
    }

    /**
     * @dataProvider blobProvider
     *
     * @covers \Kint\Utils::detectEncoding
     */
    public function testDetectEncoding(string $string, $expected, array $encodings, array $legacy)
    {
        Utils::$char_encodings = $encodings;
        Utils::$legacy_encodings = $legacy;

        $this->assertSame($expected, Utils::detectEncoding($string));
    }

    public function testDetectLegacyEncoding()
    {
        Utils::$legacy_encodings = [
            'Windows-1252',
            'Windows-1251',
        ];
        $string = \mb_convert_encoding(
            "El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso",
            'Windows-1252',
            'UTF-8'
        );
        $this->assertSame('Windows-1252', Utils::detectEncoding($string));

        Utils::$legacy_encodings = [
            'Windows-1251',
            'Windows-1252',
        ];
        $string = \mb_convert_encoding(
            'привет',
            'Windows-1251',
            'UTF-8'
        );
        $this->assertSame('Windows-1251', Utils::detectEncoding($string));

        // Yes. This is as good as it gets with those old poorly-engineered encodings. USE UTF-8!
        Utils::$legacy_encodings = [
            'Windows-1252',
            'Windows-1251',
        ];
        $this->assertSame('Windows-1252', Utils::detectEncoding($string));

        $string = \mb_convert_encoding(
            'привет Ќ',
            'Windows-1251',
            'UTF-8'
        );
        $this->assertSame('Windows-1251', Utils::detectEncoding($string));

        $string = $string."\0".$string;
        $this->assertFalse(Utils::detectEncoding($string));
    }

    /**
     * @covers \Kint\Utils::detectEncoding
     */
    public function testDetectLegacyEncodingDisabled()
    {
        Utils::$char_encodings = [
            'ASCII',
            'UTF-8',
        ];

        $string = \mb_convert_encoding("El zorro marrón rápido salta sobre<br>\r\n\tel perro perezoso", 'Windows-1252', 'UTF-8');
        $this->assertFalse(Utils::detectEncoding($string));
    }

    /**
     * @dataProvider blobProvider
     *
     * @covers \Kint\Utils::strlen
     */
    public function testStrlen(string $string, $encoding, array $encodings, array $legacy)
    {
        Utils::$char_encodings = $encodings;
        Utils::$legacy_encodings = $legacy;

        if (false === $encoding) {
            $this->assertSame(\strlen($string), Utils::strlen($string));
            $this->assertSame(\strlen($string), Utils::strlen($string, false));
        } else {
            $this->assertSame(\mb_strlen($string, $encoding), Utils::strlen($string));
            $this->assertSame(\mb_strlen($string, $encoding), Utils::strlen($string, $encoding));
        }
    }

    /**
     * @dataProvider blobProvider
     *
     * @covers \Kint\Utils::substr
     */
    public function testSubstr(string $string, $encoding, array $encodings, array $legacy)
    {
        Utils::$char_encodings = $encodings;
        Utils::$legacy_encodings = $legacy;

        $length = Utils::strlen($string);

        if (false === $encoding) {
            $this->assertSame(
                \substr($string, 1, $length - 1),
                Utils::substr($string, 1, $length - 1)
            );
            $this->assertSame(
                \substr($string, 1, $length - 1),
                Utils::substr($string, 1, $length - 1, false)
            );
        } else {
            $this->assertSame(
                \mb_substr($string, 1, $length - 1, $encoding),
                Utils::substr($string, 1, $length - 1)
            );
            $this->assertSame(
                \mb_substr($string, 1, $length - 1, $encoding),
                Utils::substr($string, 1, $length - 1, $encoding)
            );
        }
    }

    public function pathProvider()
    {
        $root_parent = '/'.\explode('/', __DIR__)[1];

        $tests = [
            'standard file' => [
                'path' => __FILE__,
                'expect' => '<tests>/UtilsTest.php',
            ],
            'standard dir' => [
                'path' => __DIR__,
                'expect' => '<tests>',
            ],
            'parent dir' => [
                'path' => KINT_DIR,
                'expect' => '<kint>',
            ],
            'sub file' => [
                'path' => KINT_DIR.'/src/test',
                'expect' => '<kint>/src/test',
            ],
            'no mangle bad paths' => [
                'path' => KINT_DIR.'/src//test',
                'expect' => '<kint>/src//test',
            ],
            'parent path' => [
                'path' => $root_parent,
                'expect' => $root_parent,
            ],
            'common path' => [
                'path' => $root_parent.'/test/test',
                'expect' => '.../test/test',
            ],
            'root path' => [
                'path' => '/',
                'expect' => '/',
            ],
            'no common path' => [
                'path' => '/asdfasdf/test',
                'expect' => '/asdfasdf/test',
            ],
            'partial folder path' => [
                'path' => __DIR__.'/partialfolder',
                'expect' => '<tests>/partialfolder',
            ],
            'phar path' => [
                'path' => 'phar:///var/test.phar/file',
                'expect' => 'phar:///var/test.phar/file',
            ],
            'common phar path' => [
                'path' => 'phar://'.$root_parent.'/test.phar/file',
                'expect' => 'phar://'.$root_parent.'/test.phar/file',
            ],
            'windows path' => [
                'path' => 'C:\\windows\\system32\\etc',
                'expect' => '<system32>/etc',
            ],
            'trailing / on folder' => [
                'path' => KINT_DIR.'/folder/',
                'expect' => '<kint>/folder/',
            ],
            'trailing / on alias' => [
                'path' => __DIR__.'/',
                'expect' => '<tests>',
            ],
            'trailing / on parent path' => [
                'path' => $root_parent.'/',
                'expect' => $root_parent.'/',
            ],
            'trailing / on common path' => [
                'path' => $root_parent.'/test/test/',
                'expect' => '.../test/test/',
            ],
            'trailing / on no common path' => [
                'path' => '/asdfasdf/test/',
                'expect' => '/asdfasdf/test/',
            ],
            'trailing / on partial folder path' => [
                'path' => __DIR__.'/partialfolder/',
                'expect' => '<tests>/partialfolder/',
            ],
            'trailing / on phar path' => [
                'path' => 'phar:///var/test.phar/file/',
                'expect' => 'phar:///var/test.phar/file/',
            ],
            'trailing / on common phar path' => [
                'path' => 'phar://'.$root_parent.'/test.phar/file/',
                'expect' => 'phar://'.$root_parent.'/test.phar/file/',
            ],
            'trailing / on windows path' => [
                'path' => 'C:\\windows\\system32\\etc\\',
                'expect' => '<system32>/etc/',
            ],
            'trailing // on folder' => [
                'path' => KINT_DIR.'/folder//',
                'expect' => '<kint>/folder//',
            ],
            'trailing // on alias' => [
                'path' => __DIR__.'//',
                'expect' => '<tests>',
            ],
            'trailing // on parent path' => [
                'path' => $root_parent.'//',
                'expect' => $root_parent.'//',
            ],
            'trailing // on common path' => [
                'path' => $root_parent.'/test/test//',
                'expect' => '.../test/test//',
            ],
            'trailing // on no common path' => [
                'path' => '/asdfasdf/test//',
                'expect' => '/asdfasdf/test//',
            ],
            'trailing // on partial folder path' => [
                'path' => __DIR__.'/partialfolder//',
                'expect' => '<tests>/partialfolder//',
            ],
            'trailing // on phar path' => [
                'path' => 'phar:///var/test.phar/file//',
                'expect' => 'phar:///var/test.phar/file//',
            ],
            'trailing // on common phar path' => [
                'path' => 'phar://'.$root_parent.'/test.phar/file//',
                'expect' => 'phar://'.$root_parent.'/test.phar/file//',
            ],
            'trailing // on windows path' => [
                'path' => 'C:\\windows\\system32\\etc\\\\',
                'expect' => '<system32>/etc//',
            ],
        ];

        if (\getenv('KINT_PHAR_TEST')) {
            $tests['common path']['path'] = 'phar://'.$tests['common path']['path'];
            $tests['trailing / on common path']['path'] = 'phar://'.$tests['trailing / on common path']['path'];
            $tests['trailing // on common path']['path'] = 'phar://'.$tests['trailing // on common path']['path'];
            $tests['common phar path']['expect'] = '.../test.phar/file';
            $tests['trailing / on common phar path']['expect'] = '.../test.phar/file/';
            $tests['trailing // on common phar path']['expect'] = '.../test.phar/file//';
        }

        return $tests;
    }

    /**
     * @dataProvider pathProvider
     *
     * @covers \Kint\Utils::shortenPath
     */
    public function testShortenPath(string $path, string $expect)
    {
        Utils::$path_aliases = [
            KINT_DIR => '<kint>',
            __DIR__.'/partialfo' => '<partialfolder>',
            '' => '<Nothing!>',
            __DIR__ => '<tests>',
            KINT_DIR.'/tes' => '<tes>',
            'C:\\windows\\system32' => '<system32>',
        ];

        $this->assertSame($expect, Utils::shortenPath($path));
    }
}
