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

namespace Kint\Renderer;

use Kint\Kint;
use Kint\Parser;
use Kint\Parser\PluginInterface as ParserPluginInterface;
use Kint\Renderer\Text\PluginInterface;
use Kint\Utils;
use Kint\Zval\InstanceValue;
use Kint\Zval\Value;

/**
 * @psalm-import-type Encoding from \Kint\Zval\BlobValue
 * @psalm-import-type PluginMap from AbstractRenderer
 */
class TextRenderer extends AbstractRenderer
{
    /**
     * TextRenderer plugins should implement PluginInterface.
     *
     * @psalm-var class-string<PluginInterface>[]
     */
    public static array $plugins = [
        'array_limit' => Text\ArrayLimitPlugin::class,
        'blacklist' => Text\BlacklistPlugin::class,
        'depth_limit' => Text\DepthLimitPlugin::class,
        'enum' => Text\EnumPlugin::class,
        'splfileinfo' => Text\SplFileInfoPlugin::class,
        'iterator_primary' => Text\IteratorPrimaryPlugin::class,
        'microtime' => Text\MicrotimePlugin::class,
        'recursion' => Text\RecursionPlugin::class,
        'stream' => Text\StreamPlugin::class,
        'trace' => Text\TracePlugin::class,
    ];

    /**
     * Parser plugins must be instanceof one of these or
     * it will be removed for performance reasons.
     *
     * @psalm-var class-string<ParserPluginInterface>[]
     */
    public static array $parser_plugin_whitelist = [
        Parser\ArrayLimitPlugin::class,
        Parser\ArrayObjectPlugin::class,
        Parser\BlacklistPlugin::class,
        Parser\DomPlugin::class,
        Parser\DOMDocumentPlugin::class,
        Parser\EnumPlugin::class,
        Parser\IteratorPlugin::class,
        Parser\MicrotimePlugin::class,
        Parser\SimpleXMLElementPlugin::class,
        Parser\SplFileInfoPlugin::class,
        Parser\StreamPlugin::class,
        Parser\TracePlugin::class,
    ];

    /**
     * The maximum length of a string before it is truncated.
     *
     * Falsey to disable
     */
    public static int $strlen_max = 0;

    /**
     * Timestamp to print in footer in date() format.
     */
    public static ?string $timestamp = null;

    /**
     * The default width of the terminal for headers.
     */
    public static int $default_width = 80;

    /**
     * Indentation width.
     */
    public static int $default_indent = 4;

    /**
     * Sort mode for object properties.
     */
    public static int $sort = self::SORT_NONE;

    /**
     * Decorate the header and footer.
     */
    public static bool $decorations = true;

    public int $header_width = 80;
    public int $indent_width = 4;

    protected array $plugin_objs = [];

    public function __construct()
    {
        parent::__construct();
        $this->header_width = self::$default_width;
        $this->indent_width = self::$default_indent;
    }

    public function render(Value $o): string
    {
        $render_spl_ids_stash = $this->render_spl_ids;

        if ($this->render_spl_ids && isset($o->hints['omit_spl_id'])) {
            $this->render_spl_ids = false;
        }

        if ($plugin = $this->getPlugin(self::$plugins, $o->hints)) {
            $output = $plugin->render($o);
            if (null !== $output && \strlen($output)) {
                if (!$this->render_spl_ids && $render_spl_ids_stash) {
                    $this->render_spl_ids = true;
                }

                return $output;
            }
        }

        $out = '';

        if (0 === $o->depth) {
            $out .= $this->colorTitle($this->renderTitle($o)).PHP_EOL;
        }

        $out .= $this->renderHeader($o);
        $out .= $this->renderChildren($o).PHP_EOL;

        if (!$this->render_spl_ids && $render_spl_ids_stash) {
            $this->render_spl_ids = true;
        }

        return $out;
    }

    public function renderNothing(): string
    {
        if (self::$decorations) {
            return $this->colorTitle(
                $this->boxText('No argument', $this->header_width)
            ).PHP_EOL;
        }

        return $this->colorTitle('No argument').PHP_EOL;
    }

    public function boxText(string $text, int $width): string
    {
        $out = '┌'.\str_repeat('─', $width - 2).'┐'.PHP_EOL;

        if (\strlen($text)) {
            $text = Utils::truncateString($text, $width - 4);
            $text = \str_pad($text, $width - 4);

            $out .= '│ '.$this->escape($text).' │'.PHP_EOL;
        }

        $out .= '└'.\str_repeat('─', $width - 2).'┘';

        return $out;
    }

    public function renderTitle(Value $o): string
    {
        $name = $o->getName();

        if (self::$decorations) {
            return $this->boxText($name, $this->header_width);
        }

        return Utils::truncateString($name, $this->header_width);
    }

    public function renderHeader(Value $o): string
    {
        $output = [];

        if ($o->depth) {
            if (null !== ($s = $o->getModifiers())) {
                $output[] = $s;
            }

            $output[] = $this->escape(\var_export($o->name, true));

            if (null !== ($s = $o->getOperator())) {
                $output[] = $this->escape($s);
            }
        }

        if (null !== ($s = $o->getType())) {
            if ($o->reference) {
                $s = '&'.$s;
            }

            $s = $this->colorType($this->escape($s));

            if ($o instanceof InstanceValue && $this->shouldRenderObjectIds()) {
                $s .= '#'.$o->spl_object_id;
            }

            $output[] = $s;
        }

        if (null !== ($s = $o->getSize())) {
            $output[] = '('.$this->escape($s).')';
        }

        if (null !== ($s = $o->getValueShort())) {
            if (self::$strlen_max) {
                $s = Utils::truncateString($s, self::$strlen_max);
            }
            $output[] = $this->colorValue($this->escape($s));
        }

        return \str_repeat(' ', $o->depth * $this->indent_width).\implode(' ', $output);
    }

    public function renderChildren(Value $o): string
    {
        if ('array' === $o->type) {
            $output = ' [';
        } elseif ('object' === $o->type) {
            $output = ' (';
        } else {
            return '';
        }

        $children = '';

        if ($o->value && \is_array($o->value->contents)) {
            /**
             * @psalm-suppress PossiblyNullReference
             * Psalm bug #11052
             */
            if ($o instanceof InstanceValue && 'properties' === $o->value->getName()) {
                foreach (self::sortProperties($o->value->contents, self::$sort) as $obj) {
                    $children .= $this->render($obj);
                }
            } else {
                foreach ($o->value->contents as $child) {
                    $children .= $this->render($child);
                }
            }
        }

        if ($children) {
            $output .= PHP_EOL.$children;
            $output .= \str_repeat(' ', $o->depth * $this->indent_width);
        }

        if ('array' === $o->type) {
            $output .= ']';
        } else {
            $output .= ')';
        }

        return $output;
    }

    public function colorValue(string $string): string
    {
        return $string;
    }

    public function colorType(string $string): string
    {
        return $string;
    }

    public function colorTitle(string $string): string
    {
        return $string;
    }

    public function postRender(): string
    {
        if (self::$decorations) {
            $output = \str_repeat('═', $this->header_width);
        } else {
            $output = '';
        }

        if (!$this->show_trace) {
            return $this->colorTitle($output);
        }

        if ($output) {
            $output .= PHP_EOL;
        }

        return $this->colorTitle($output.$this->calledFrom().PHP_EOL);
    }

    public function filterParserPlugins(array $plugins): array
    {
        $return = [];

        foreach ($plugins as $plugin) {
            foreach (self::$parser_plugin_whitelist as $whitelist) {
                if ($plugin instanceof $whitelist) {
                    $return[] = $plugin;
                    continue 2;
                }
            }
        }

        return $return;
    }

    public function ideLink(string $file, int $line): string
    {
        return $this->escape(Kint::shortenPath($file)).':'.$line;
    }

    /**
     * @psalm-param Encoding $encoding
     */
    public function escape(string $string, $encoding = false): string
    {
        return $string;
    }

    protected function calledFrom(): string
    {
        $output = '';

        if (isset($this->call_info['callee']['file'])) {
            $output .= 'Called from '.$this->ideLink(
                $this->call_info['callee']['file'],
                $this->call_info['callee']['line']
            );
        }

        if (
            isset($this->call_info['callee']['function']) &&
            (
                !empty($this->call_info['callee']['class']) ||
                !\in_array(
                    $this->call_info['callee']['function'],
                    ['include', 'include_once', 'require', 'require_once'],
                    true
                )
            )
        ) {
            $output .= ' [';
            $output .= $this->call_info['callee']['class'] ?? '';
            $output .= $this->call_info['callee']['type'] ?? '';
            $output .= $this->call_info['callee']['function'].'()]';
        }

        if (null !== self::$timestamp) {
            if (\strlen($output)) {
                $output .= ' ';
            }
            $output .= \date(self::$timestamp);
        }

        return $output;
    }

    /**
     * @psalm-param PluginMap $plugins
     * @psalm-param array<string, true> $hints
     */
    protected function getPlugin(array $plugins, array $hints): ?PluginInterface
    {
        if ($overlap = \array_intersect_key($hints, $plugins)) {
            $plugin = $plugins[\array_key_last($overlap)];

            if (!isset($this->plugin_objs[$plugin]) && \is_a($plugin, PluginInterface::class, true)) {
                $this->plugin_objs[$plugin] = new $plugin($this);
            }

            return $this->plugin_objs[$plugin];
        }

        return null;
    }
}
