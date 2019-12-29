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
use Kint\Object\Representation\ColorRepresentation;
use Kint\Parser\BlacklistPlugin;
use Kint\Parser\FsPathPlugin;
use Kint\Parser\ToStringPlugin;
use Kint\Renderer\RichRenderer;
use Kint\Renderer\TextRenderer;
use PHPUnit\Framework\TestCase;
use PHPUnit_Util_InvalidArgumentHelper;

abstract class KintTestCase extends TestCase
{
    protected $kint_statics;
    protected $rich_statics;
    protected $char_encodings;
    protected $legacy_encodings;
    protected $text_decorations;
    protected $text_plugin_whitelist;
    protected $color_map;
    protected $blacklist_plugin_blacklist;
    protected $blacklist_plugin_shallow_blacklist;
    protected $blacklist_plugin_array_limit;
    protected $blacklist_plugin_shallow_array_limit;
    protected $fspath_plugin_blacklist;
    protected $tostring_plugin_blacklist;

    protected function setUp()
    {
        parent::setUp();

        $this->kint_statics = Kint::getStatics();
        $this->rich_statics = [
            'folder' => RichRenderer::$folder,
            'needs_pre_render' => RichRenderer::$needs_pre_render,
            'needs_folder_render' => RichRenderer::$needs_folder_render,
            'always_pre_render' => RichRenderer::$always_pre_render,
        ];
        $this->char_encodings = BlobObject::$char_encodings;
        $this->legacy_encodings = BlobObject::$legacy_encodings;
        $this->text_decorations = TextRenderer::$decorations;
        $this->text_plugin_whitelist = TextRenderer::$parser_plugin_whitelist;
        $this->color_map = ColorRepresentation::$color_map;
        $this->blacklist_plugin_blacklist = BlacklistPlugin::$blacklist;
        $this->blacklist_plugin_shallow_blacklist = BlacklistPlugin::$shallow_blacklist;
        $this->blacklist_plugin_array_limit = BlacklistPlugin::$array_limit;
        $this->blacklist_plugin_shallow_array_limit = BlacklistPlugin::$shallow_array_limit;
        $this->fspath_plugin_blacklist = FsPathPlugin::$blacklist;
        $this->tostring_plugin_blacklist = ToStringPlugin::$blacklist;
    }

    protected function tearDown()
    {
        parent::tearDown();

        foreach ($this->kint_statics as $key => $val) {
            Kint::${$key} = $val;
        }

        foreach ($this->rich_statics as $key => $val) {
            RichRenderer::${$key} = $val;
        }

        BlobObject::$char_encodings = $this->char_encodings;
        BlobObject::$legacy_encodings = $this->legacy_encodings;
        TextRenderer::$decorations = $this->text_decorations;
        TextRenderer::$parser_plugin_whitelist = $this->text_plugin_whitelist;
        ColorRepresentation::$color_map = $this->color_map;
        BlacklistPlugin::$blacklist = $this->blacklist_plugin_blacklist;
        BlacklistPlugin::$shallow_blacklist = $this->blacklist_plugin_shallow_blacklist;
        BlacklistPlugin::$array_limit = $this->blacklist_plugin_array_limit;
        BlacklistPlugin::$shallow_array_limit = $this->blacklist_plugin_shallow_array_limit;
        FsPathPlugin::$blacklist = $this->fspath_plugin_blacklist;
        ToStringPlugin::$blacklist = $this->tostring_plugin_blacklist;
    }

    /**
     * Asserts that a condition is true.
     *
     * @param string $actual
     * @param string $message
     *
     * @throws PHPUnit_Framework_Exception
     */
    public function assertLike(array $expected, $actual, $message = '')
    {
        if (!\is_string($actual)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        self::assertThat($actual, new ContainsInOrderConstraint($expected), $message);
    }
}
