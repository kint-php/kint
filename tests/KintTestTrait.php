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
use Kint\Parser\ArrayLimitPlugin;
use Kint\Parser\BlacklistPlugin;
use Kint\Parser\FsPathPlugin;
use Kint\Parser\ToStringPlugin;
use Kint\Renderer\RichRenderer;
use Kint\Renderer\TextRenderer;
use Kint\Zval\BlobValue;
use Kint\Zval\Representation\ColorRepresentation;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit_Util_InvalidArgumentHelper;

trait KintTestTrait
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
    protected $arraylimit_plugin_trigger;
    protected $arraylimit_plugin_limit;
    protected $arraylimit_plugin_numeric_only;
    protected $fspath_plugin_blacklist;
    protected $tostring_plugin_blacklist;

    /**
     * Asserts that a condition is true.
     *
     * @param string $actual
     * @param string $message
     *
     * @throws \PHPUnit\Framework\Exception
     */
    public function assertLike(array $expected, $actual, $message = '')
    {
        if (!\is_string($actual)) {
            if (\class_exists(InvalidArgumentException::class)) {
                throw InvalidArgumentException::create(2, 'string');
            }
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        if (\class_exists('PHPUnit\\Framework\\Constraint\\Constraint')) {
            self::assertThat($actual, new ContainsInOrderConstraint($expected), $message);
        } else {
            self::assertThat($actual, new ContainsInOrder5Constraint($expected), $message); // @codeCoverageIgnore
        }
    }

    protected function kintUp()
    {
        $this->kint_statics = Kint::getStatics();
        $this->rich_statics = [
            'folder' => RichRenderer::$folder,
            'needs_pre_render' => RichRenderer::$needs_pre_render,
            'needs_folder_render' => RichRenderer::$needs_folder_render,
            'always_pre_render' => RichRenderer::$always_pre_render,
        ];
        $this->char_encodings = BlobValue::$char_encodings;
        $this->legacy_encodings = BlobValue::$legacy_encodings;
        $this->text_decorations = TextRenderer::$decorations;
        $this->text_plugin_whitelist = TextRenderer::$parser_plugin_whitelist;
        $this->color_map = ColorRepresentation::$color_map;
        $this->blacklist_plugin_blacklist = BlacklistPlugin::$blacklist;
        $this->blacklist_plugin_shallow_blacklist = BlacklistPlugin::$shallow_blacklist;
        $this->arraylimit_plugin_trigger = ArrayLimitPlugin::$trigger;
        $this->arraylimit_plugin_limit = ArrayLimitPlugin::$limit;
        $this->arraylimit_plugin_numeric_only = ArrayLimitPlugin::$numeric_only;
        $this->fspath_plugin_blacklist = FsPathPlugin::$blacklist;
        $this->tostring_plugin_blacklist = ToStringPlugin::$blacklist;
    }

    protected function kintDown()
    {
        foreach ($this->kint_statics as $key => $val) {
            Kint::${$key} = $val;
        }

        foreach ($this->rich_statics as $key => $val) {
            RichRenderer::${$key} = $val;
        }

        BlobValue::$char_encodings = $this->char_encodings;
        BlobValue::$legacy_encodings = $this->legacy_encodings;
        TextRenderer::$decorations = $this->text_decorations;
        TextRenderer::$parser_plugin_whitelist = $this->text_plugin_whitelist;
        ColorRepresentation::$color_map = $this->color_map;
        BlacklistPlugin::$blacklist = $this->blacklist_plugin_blacklist;
        BlacklistPlugin::$shallow_blacklist = $this->blacklist_plugin_shallow_blacklist;
        ArrayLimitPlugin::$trigger = $this->arraylimit_plugin_trigger;
        ArrayLimitPlugin::$limit = $this->arraylimit_plugin_limit;
        ArrayLimitPlugin::$numeric_only = $this->arraylimit_plugin_numeric_only;
        FsPathPlugin::$blacklist = $this->fspath_plugin_blacklist;
        ToStringPlugin::$blacklist = $this->tostring_plugin_blacklist;
    }
}
