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

use DOMDocument;
use DOMXPath;
use Exception;
use Kint\Kint;
use Kint\Object\BlobObject;
use Kint\Parser\Parser;
use Kint\Parser\ProxyPlugin;
use Kint\Renderer\RichRenderer;
use Kint\Renderer\TextRenderer;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Exception;

class IntegrationTest extends KintTestCase
{
    protected function setUp()
    {
        parent::setUp();

        // Helps immensely with trace performance through phpunit
        Kint::$max_depth = 3;
    }

    /**
     * @covers \d
     * @covers \Kint\Kint::dump
     * @covers \s
     */
    public function testBasicDumps()
    {
        $testdata = [
            1234,
            (object) ['abc' => 'def'],
            1234.5678,
            'Good news everyone! I\'ve got some bad news!',
            null,
        ];

        $testdata[] = &$testdata;

        $array_structure = [
            '0', 'integer', '1234',
            '1', 'stdClass', '1',
            'public', 'abc', 'string', '3', 'def',
            '2', 'double', '1234.5678',
            '3', 'string', '43', 'Good news everyone! I\'ve got some bad news!',
            '4', 'null',
        ];

        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;

        Kint::$enabled_mode = Kint::MODE_RICH;
        $richbase = d($testdata);

        $this->assertLike(
            \array_merge(
                $array_structure,
                ['&amp;array', '6'],
                $array_structure,
                ['&amp;array', 'Recursion']
            ),
            $richbase
        );

        Kint::$enabled_mode = true;
        $this->assertSame($richbase, d($testdata));
        $this->assertSame($richbase, Kint::dump($testdata));

        Kint::$enabled_mode = Kint::MODE_PLAIN;
        $plainbase = d($testdata);

        $this->assertLike(
            \array_merge(
                $array_structure,
                ['&amp;array', '6'],
                $array_structure,
                ['&amp;array', 'RECURSION']
            ),
            $plainbase
        );

        $this->assertSame($plainbase, Kint::dump($testdata));

        Kint::$enabled_mode = true;
        $this->assertSame($plainbase, s($testdata));

        Kint::$enabled_mode = Kint::MODE_CLI;
        $clibase = d($testdata);

        $this->assertLike(
            \array_merge(
                $array_structure,
                ['&array', '6'],
                $array_structure,
                ['&array', 'RECURSION']
            ),
            $clibase
        );

        $this->assertSame($clibase, Kint::dump($testdata));

        Kint::$enabled_mode = true;
        Kint::$cli_detection = true;
        $this->assertSame($clibase, d($testdata));
        $this->assertSame($clibase, s($testdata));
        Kint::$cli_detection = false;

        Kint::$enabled_mode = Kint::MODE_TEXT;
        $textbase = d($testdata);

        $this->assertLike(
            \array_merge(
                $array_structure,
                ['&array', '6'],
                $array_structure,
                ['&array', 'RECURSION']
            ),
            $textbase
        );

        $this->assertSame($textbase, Kint::dump($testdata));

        Kint::$return = false;
        Kint::$enabled_mode = true;
        \ob_start();
        ~d($testdata);
        $this->assertSame($textbase, \ob_get_clean());

        \ob_start();
        Kint::$enabled_mode = false;
        $this->assertSame(0, d($testdata));
        $this->assertSame(0, s($testdata));

        $this->assertSame('', \ob_get_clean());
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testDumpBadMode()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $d1 = Kint::dump(1234);

        Kint::$enabled_mode = 'This is not a real mode';
        $d2 = Kint::dump(1234);

        $this->assertSame($d1, $d2);
    }

    /**
     * @covers \Kint\Kint::dump
     * @covers \Kint\Kint::trace
     */
    public function testFlushModifier()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $base_level = \ob_get_level();
        \ob_start();
        $this->assertSame($base_level + 1, \ob_get_level());
        Kint::dump(1234);
        $this->assertSame($base_level + 1, \ob_get_level());
        // Please leave the ! modifier in place, to prevent errors using unary - on a returned string
        -!Kint::dump(1234);
        $this->assertSame(0, \ob_get_level());

        \ob_start();
        $this->assertSame(1, \ob_get_level());
        Kint::trace();
        $this->assertSame(1, \ob_get_level());
        // Please leave the ! modifier in place, to prevent errors using unary - on a returned string
        -!Kint::trace();
        $this->assertSame(0, \ob_get_level());

        while ($base_level > \ob_get_level()) {
            \ob_start();
        }
        $this->assertSame($base_level, \ob_get_level());
    }

    /**
     * @covers \Kint\Kint::dump
     * @covers \Kint\Renderer\RichRenderer::renderHeaderWrapper
     * @covers \Kint\Renderer\RichRenderer::setCallInfo
     * @covers \Kint\Renderer\RichRenderer::setStatics
     */
    public function testExpandModifier()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_RICH;
        RichRenderer::$folder = false;

        $value = ['a' => [1, 2, 3], 'b' => 'c'];

        $d1 = Kint::dump($value);

        Kint::$expanded = true;
        $d2 = Kint::dump($value);

        Kint::$return = false;
        Kint::$expanded = false;
        RichRenderer::$needs_pre_render = true;
        \ob_start();
        !Kint::dump($value);
        $d3 = \ob_get_clean();

        $this->assertNotSame($d1, $d3);
        $this->assertSame($d2, $d3);

        \libxml_use_internal_errors(true);

        $d1dom = new DOMDocument();
        $d1dom->loadHtml($d1);

        $d3dom = new DOMDocument();
        $d3dom->loadHtml($d3);

        $d3x = new DOMXPath($d3dom);
        $classattrs = $d3x->query('//dt[contains(@class, "kint-parent")]/@class');

        foreach ($classattrs as $attr) {
            $vals = \explode(' ', $attr->value);
            $vals = \array_diff($vals, ['kint-show']);
            $attr->value = \implode(' ', $vals);
        }

        $this->assertSame($d1dom->saveHtml(), $d3dom->saveHtml());
    }

    /**
     * @covers \Kint\Kint::dump
     * @covers \Kint\Renderer\RichRenderer::renderHeaderWrapper
     * @covers \Kint\Renderer\RichRenderer::setCallInfo
     * @covers \Kint\Renderer\RichRenderer::setStatics
     */
    public function testExpandModifierFolder()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_RICH;
        RichRenderer::$folder = true;

        $value = ['a' => [1, 2, 3], 'b' => 'c'];

        $d1 = Kint::dump($value);

        \libxml_use_internal_errors(true);

        $d1dom = new DOMDocument();
        $d1dom->loadHtml('<body>'.$d1.'</body>');
        $d1x = new DOMXPath($d1dom);
        $nodes1 = $d1x->query('//div[contains(@class, "kint-folder")]');

        $this->assertSame(1, $nodes1->length);

        Kint::$return = false;
        RichRenderer::$needs_folder_render = true;
        RichRenderer::$needs_pre_render = true;
        \ob_start();
        !Kint::dump($value);
        $d2 = \ob_get_clean();

        $d2dom = new DOMDocument();
        $d2dom->loadHtml('<body>'.$d2.'</body>');
        $d2x = new DOMXPath($d2dom);
        $nodes2 = $d2x->query('//div[contains(@class, "kint-folder")]');

        $this->assertSame(0, $nodes2->length);

        $classattrs = $d2x->query('//dt[contains(@class, "kint-parent")]/@class');

        foreach ($classattrs as $attr) {
            $vals = \explode(' ', $attr->value);
            $vals = \array_diff($vals, ['kint-show']);
            $attr->value = \implode(' ', $vals);
        }

        $classattrs = $d1x->query('//div[contains(@class, "kint-rich")]/@class');

        foreach ($classattrs as $attr) {
            $vals = \explode(' ', $attr->value);
            $vals = \array_diff($vals, ['kint-file']);
            $attr->value = \implode(' ', $vals);
        }

        $nodes1 = \iterator_to_array($nodes1);
        $nodes1[0]->parentNode->removeChild($nodes1[0]);

        $this->assertSame($d1dom->saveHtml(), $d2dom->saveHtml());
    }

    /**
     * @covers \Kint\Kint::dump
     * @covers \Kint\Kint::trace
     */
    public function testTextModifier()
    {
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        TextRenderer::$decorations = false;

        $value = ['a' => [1, 2, 3], 'b' => 'c'];

        Kint::$return = false;
        Kint::$enabled_mode = Kint::MODE_RICH;

        \ob_start();
        ~Kint::dump($value);
        $d1 = \ob_get_clean();

        Kint::$enabled_mode = Kint::MODE_TEXT;
        Kint::$return = true;
        $d2 = Kint::dump($value);

        $this->assertSame($d1, $d2);

        Kint::$enabled_mode = Kint::MODE_RICH;
        Kint::$return = false;

        \ob_start();
        ~Kint::trace();
        $d1 = \ob_get_clean();
        $bt = \debug_backtrace(true);
        \array_unshift($bt, [
            'line' => __LINE__ - 4,
            'class' => 'Kint\\Kint',
            'function' => 'trace',
            'file' => __FILE__,
        ]);

        Kint::$enabled_mode = Kint::MODE_TEXT;
        Kint::$return = true;

        $d2 = \preg_replace('/^\\$bt\\b/', 'Kint\\Kint::trace()', Kint::dump($bt));

        $this->assertSame($d1, $d2);
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testDeepModifier()
    {
        Kint::$return = false;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$max_depth = 1;
        Kint::$enabled_mode = Kint::MODE_TEXT;

        $value = ['a' => [1, 2, 3], 'b' => 'c'];

        \ob_start();
        +Kint::dump($value);
        $d1 = \ob_get_clean();

        Kint::$return = true;
        $d2 = Kint::dump($value);

        $this->assertNotSame($d1, $d2);

        Kint::$max_depth = 0;
        $d2 = Kint::dump($value);

        $this->assertSame($d1, $d2);
    }

    /**
     * @covers \Kint\Kint::dump
     * @covers \Kint\Renderer\RichRenderer::setCallInfo
     */
    public function testReturnModifier()
    {
        Kint::$return = false;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_RICH;

        $value = ['a' => [1, 2, 3], 'b' => 'c'];

        \ob_start();
        $d1 = @Kint::dump($value);
        $out = \ob_get_clean();

        Kint::$return = true;
        $d2 = Kint::dump($value);

        $this->assertSame($d1, $d2);
        $this->assertEmpty($out);
    }

    /**
     * @covers \Kint\Kint::dump
     * @covers \Kint\Kint::trace
     */
    public function testTrace()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $bt = \debug_backtrace(true);
        \array_unshift($bt, [
            'class' => 'Kint\\Kint',
            'file' => __FILE__,
        ]);

        $d2 = Kint::dump(1);
        $bt[0]['line'] = __LINE__ - 1;
        $bt[0]['function'] = 'dump';
        $d1 = \preg_replace('/^\\$bt\\b/', 'Kint\\Kint::dump(1)', Kint::dump($bt));
        $this->assertSame($d1, $d2);

        $d2 = Kint::trace();
        $bt[0]['line'] = __LINE__ - 1;
        $bt[0]['function'] = 'trace';
        $d1 = \preg_replace('/^\\$bt\\b/', 'Kint\\Kint::trace()', Kint::dump($bt));
        $this->assertSame($d1, $d2);

        Kint::$return = false;

        \ob_start();
        Kint::trace();
        $d2 = \ob_get_clean();
        $bt[0]['line'] = __LINE__ - 2;
        $bt[0]['function'] = 'trace';
        Kint::$return = true;
        $d1 = \preg_replace('/^\\$bt\\b/', 'Kint\\Kint::trace()', Kint::dump($bt));
        $this->assertSame($d1, $d2);

        Kint::$enabled_mode = false;

        $this->assertSame(0, Kint::trace());
    }

    /**
     * @covers \Kint\Kint::dump
     * @covers \Kint\Kint::trace
     */
    public function testToplevelTrace()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $firstframe = \end($bt);

        if (isset($firstframe['class'])) {
            Kint::$aliases[] = [$firstframe['class'], $firstframe['function']];
        } else {
            Kint::$aliases[] = $firstframe['function'];
        }

        $d1 = Kint::dump(1);
        $d2 = Kint::trace();

        $d1 = \explode("\n", $d1);
        \array_shift($d1);
        $d1 = \implode("\n", $d1);

        $d2 = \explode("\n", $d2);
        \array_shift($d2);
        $d2 = \implode("\n", $d2);

        $this->assertSame($d1, $d2);

        $this->assertLike(
            [
                'Debug Backtrace (1):',
                Kint::shortenPath($firstframe['file']).':'.$firstframe['line'],
            ],
            $d1
        );
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testToplevelTracedumpNoCallerMatch()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;
        Kint::$aliases = [];

        $bt = \debug_backtrace(true);
        \array_unshift($bt, [
            'class' => 'Kint\\Kint',
            'file' => __FILE__,
        ]);

        $d2 = Kint::dump(1);
        $bt[0]['line'] = __LINE__ - 1;
        $bt[0]['function'] = 'dump';
        Kint::$aliases = [['Kint\\Kint', 'dump']];
        $d1 = \preg_replace('/^\\$bt\\b/', 'Kint\\Kint::dump(1)', Kint::dump($bt));

        $this->assertSame($d1, $d2);
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testDumpNothing()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $d = Kint::dump();
        $this->assertSame("No argument\n", $d);
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testNoParamNames()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_RICH;
        TextRenderer::$decorations = false;

        $values = [[1], [2], [3]];

        $d = \call_user_func_array('Kint::dump', $values);
        $this->assertLike(
            [
                '$0[0]',
                '$1[0]',
                '$2[0]',
            ],
            $d
        );
    }

    /**
     * @covers \Kint\Kint::dump
     */
    public function testPlugins()
    {
        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;
        Kint::$enabled_mode = Kint::MODE_TEXT;
        TextRenderer::$decorations = false;

        $p1_triggered = false;
        $p1 = new ProxyPlugin(
            ['resource'],
            Parser::TRIGGER_SUCCESS,
            function () use (&$p1_triggered) {
                $p1_triggered = true;
            }
        );

        $value = \fopen(__FILE__, 'r');

        try {
            Kint::$plugins = [];
            $d1 = Kint::dump($value);

            Kint::$plugins = [
                $p1,
                'Kint\\Parser\\StreamPlugin',
            ];
            TextRenderer::$parser_plugin_whitelist = ['Kint\\Parser\\Plugin'];

            $this->assertFalse($p1_triggered);

            $d2 = Kint::dump($value);

            \fclose($value);
        } catch (Exception $e) {
            \fclose($value);

            throw $e;
        }

        $this->assertTrue($p1_triggered);
        $this->assertLike(
            [
                '$value',
                'stream resource',
                Kint::shortenPath(__FILE__),
            ],
            $d2
        );
        $this->assertNotSame($d1, $d2);
    }

    /**
     * Test this test suite's restore after test.
     *
     * @covers \Kint\Test\KintTestCase::setUp
     * @covers \Kint\Test\KintTestCase::tearDown
     */
    public function testStore()
    {
        Kint::$file_link_format = 'test_store';
        $this->assertSame('test_store', Kint::$file_link_format);
        BlobObject::$char_encodings[] = 'this_is_not_a_real_encoding';
        $this->assertContains('this_is_not_a_real_encoding', BlobObject::$char_encodings);
        BlobObject::$legacy_encodings[] = 'this_is_also_not_a_real_encoding';
        $this->assertContains('this_is_also_not_a_real_encoding', BlobObject::$legacy_encodings);
    }

    /**
     * @covers \Kint\Test\KintTestCase::setUp
     * @covers \Kint\Test\KintTestCase::tearDown
     */
    public function testRestore()
    {
        $this->assertNotSame('test_store', Kint::$file_link_format);
        $this->assertNotContains('this_is_not_a_real_encoding', BlobObject::$char_encodings);
        $this->assertNotContains('this_is_also_not_a_real_encoding', BlobObject::$legacy_encodings);
    }

    /**
     * @covers \Kint\Test\KintTestCase::assertLike
     */
    public function testLike()
    {
        $this->assertLike(['a', 'b', 'c'], 'foo a bar baz c buzz');
    }

    /**
     * @covers \Kint\Test\KintTestCase::assertLike
     */
    public function testNotLike()
    {
        $caught = false;

        try {
            $this->assertLike(['a', 'b', 'c', 'o'], 'foo a bar baz c buzz');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $caught = true;
        }

        $this->assertTrue($caught, 'Failed to mismatch');
    }

    /**
     * @covers \Kint\Test\KintTestCase::assertLike
     */
    public function testLikeNonString()
    {
        $caught = false;

        try {
            $this->assertLike(['a', 'b', 'c'], ['a', 'b', 'c']);
        } catch (PHPUnit_Framework_Exception $e) {
            $caught = true;
        }

        $this->assertTrue($caught, 'Failed to throw');
    }
}
