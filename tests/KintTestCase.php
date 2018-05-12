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
use Kint\Object\BlobObject;
use Kint\Renderer\TextRenderer;
use PHPUnit_Framework_TestCase;
use PHPUnit_Util_InvalidArgumentHelper;

abstract class KintTestCase extends PHPUnit_Framework_TestCase
{
    protected $kint_statics;
    protected $char_encodings;
    protected $legacy_encodings;
    protected $text_decorations;
    protected $text_plugin_whitelist;

    public function setUp()
    {
        $this->kint_statics = Kint::getStatics();
        $this->char_encodings = BlobObject::$char_encodings;
        $this->legacy_encodings = BlobObject::$legacy_encodings;
        $this->text_decorations = TextRenderer::$decorations;
        $this->text_plugin_whitelist = TextRenderer::$parser_plugin_whitelist;
    }

    public function tearDown()
    {
        foreach ($this->kint_statics as $key => $val) {
            Kint::${$key} = $val;
        }

        BlobObject::$char_encodings = $this->char_encodings;
        BlobObject::$legacy_encodings = $this->legacy_encodings;
        TextRenderer::$decorations = $this->text_decorations;
        TextRenderer::$parser_plugin_whitelist = $this->text_plugin_whitelist;
    }

    /**
     * Asserts that a condition is true.
     *
     * @param array  $expected
     * @param string $actual
     * @param string $message
     *
     * @throws PHPUnit_Framework_Exception
     */
    public function assertLike(array $expected, $actual, $message = '')
    {
        if (!is_string($actual)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        self::assertThat($actual, new ContainsInOrderConstraint($expected), $message);
    }
}
