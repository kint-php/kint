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

use Kint\Kint;
use Kint\Parser\ArrayLimitPlugin;
use Kint\Parser\BlacklistPlugin;
use Kint\Parser\ClassHooksPlugin;
use Kint\Parser\ClassMethodsPlugin;
use Kint\Parser\ClassStringsPlugin;
use Kint\Parser\DomPlugin;
use Kint\Parser\FsPathPlugin;
use Kint\Parser\SerializePlugin;
use Kint\Parser\ToStringPlugin;
use Kint\Parser\TracePlugin;
use Kint\Parser\XmlPlugin;
use Kint\Renderer\AbstractRenderer;
use Kint\Renderer\RichRenderer;
use Kint\Renderer\TextRenderer;
use Kint\Utils;
use Kint\Value\Representation\ColorRepresentation;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class KintTestCase extends TestCase
{
    protected $kint_statics;
    protected $rich_statics;
    protected $char_encodings;
    protected $legacy_encodings;
    protected $path_aliases;
    protected $file_link_format;
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
    protected $serialize_safe_mode;
    protected $serialize_allowed_classes;
    protected $trace_plugin_blacklist;
    protected $xml_plugin_method;
    protected $dom_plugin_blacklist;
    protected $dom_plugin_verbose;
    protected $classhooks_verbose;
    protected $classstrings_blacklist;
    protected $classmethods_show_access_path;
    protected $classmethods_show_constructor_path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kint_statics = Kint::getStatics();
        $this->rich_statics = [
            'folder' => RichRenderer::$folder,
            'needs_pre_render' => RichRenderer::$needs_pre_render,
            'always_pre_render' => RichRenderer::$always_pre_render,
        ];
        $this->char_encodings = Utils::$char_encodings;
        $this->legacy_encodings = Utils::$legacy_encodings;
        $this->path_aliases = Utils::$path_aliases;
        $this->file_link_format = AbstractRenderer::$file_link_format;
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
        $this->serialize_safe_mode = SerializePlugin::$safe_mode;
        $this->serialize_allowed_classes = SerializePlugin::$allowed_classes;
        $this->trace_plugin_blacklist = TracePlugin::$blacklist;
        $this->trace_plugin_path_blacklist = TracePlugin::$path_blacklist;
        $this->xml_plugin_method = XmlPlugin::$parse_method;
        $this->dom_plugin_blacklist = DomPlugin::$blacklist;
        $this->dom_plugin_verbose = DomPlugin::$verbose;
        $this->classhooks_verbose = ClassHooksPlugin::$verbose;
        $this->classstrings_blacklist = ClassStringsPlugin::$blacklist;
        $this->classmethods_show_access_path = ClassMethodsPlugin::$show_access_path;
        $this->classmethods_show_constructor_path = ClassMethodsPlugin::$show_constructor_path;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->kint_statics as $key => $val) {
            Kint::${$key} = $val;
        }

        foreach ($this->rich_statics as $key => $val) {
            RichRenderer::${$key} = $val;
        }

        Utils::$char_encodings = $this->char_encodings;
        Utils::$legacy_encodings = $this->legacy_encodings;
        Utils::$path_aliases = $this->path_aliases;
        AbstractRenderer::$file_link_format = $this->file_link_format;
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
        SerializePlugin::$safe_mode = $this->serialize_safe_mode;
        SerializePlugin::$allowed_classes = $this->serialize_allowed_classes;
        TracePlugin::$blacklist = $this->trace_plugin_blacklist;
        TracePlugin::$path_blacklist = $this->trace_plugin_path_blacklist;
        XmlPlugin::$parse_method = $this->xml_plugin_method;
        DomPlugin::$blacklist = $this->dom_plugin_blacklist;
        DomPlugin::$verbose = $this->dom_plugin_verbose;
        ClassHooksPlugin::$verbose = $this->classhooks_verbose;
        ClassStringsPlugin::$blacklist = $this->classstrings_blacklist;
        ClassMethodsPlugin::$show_access_path = $this->classmethods_show_access_path;
        ClassMethodsPlugin::$show_constructor_path = $this->classmethods_show_constructor_path;
    }

    public function assertLike(array $expected, string $actual, string $message = '')
    {
        self::assertThat($actual, new ContainsInOrderConstraint($expected), $message);
    }
}
