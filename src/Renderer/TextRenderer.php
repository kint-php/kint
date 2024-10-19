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
use Kint\Value\AbstractValue;
use Kint\Value\ArrayValue;
use Kint\Value\Context\ArrayContext;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\PropertyContext;
use Kint\Value\InstanceValue;
use Kint\Value\StringValue;

/**
 * @psalm-import-type Encoding from StringValue
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
        'array_limit' => Text\LockPlugin::class,
        'blacklist' => Text\LockPlugin::class,
        'depth_limit' => Text\LockPlugin::class,
        'splfileinfo' => Text\SplFileInfoPlugin::class,
        'microtime' => Text\MicrotimePlugin::class,
        'recursion' => Text\LockPlugin::class,
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
        Parser\ClosurePlugin::class,
        Parser\DateTimePlugin::class,
        Parser\DomPlugin::class,
        Parser\DOMDocumentPlugin::class,
        Parser\EnumPlugin::class,
        Parser\IteratorPlugin::class,
        Parser\MicrotimePlugin::class,
        Parser\MysqliPlugin::class,
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

    public function render(AbstractValue $o): string
    {
        $render_spl_ids_stash = $this->render_spl_ids;

        if ($this->render_spl_ids && $o->hasHint('omit_spl_id')) {
            $this->render_spl_ids = false;
        }

        if ($plugin = $this->getPlugin(self::$plugins, $o)) {
            $output = $plugin->render($o);
            if (null !== $output && \strlen($output)) {
                if (!$this->render_spl_ids && $render_spl_ids_stash) {
                    $this->render_spl_ids = true;
                }

                return $output;
            }
        }

        $out = '';

        $c = $o->getContext();

        if (0 === $c->getDepth()) {
            $out .= $this->colorTitle($this->renderTitle($o)).PHP_EOL;
        }

        $out .= $header = $this->renderHeader($o);
        $out .= $this->renderChildren($o);

        if (\strlen($header)) {
            $out .= PHP_EOL;
        }

        if (!$this->render_spl_ids && $render_spl_ids_stash) {
            $this->render_spl_ids = true;
        }

        return $out;
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

    public function renderTitle(AbstractValue $o): string
    {
        if (self::$decorations) {
            return $this->boxText($o->getDisplayName(), $this->header_width);
        }

        return Utils::truncateString($o->getDisplayName(), $this->header_width);
    }

    public function renderHeader(AbstractValue $o): string
    {
        $output = [];

        $c = $o->getContext();

        if ($c->getDepth() > 0) {
            if ($c instanceof ClassDeclaredContext) {
                $output[] = $this->colorType($c->getModifiers());
            }

            if ($c instanceof ArrayContext) {
                $output[] = $this->escape(\var_export($o->getContext()->getName(), true));
            } else {
                $output[] = $this->escape((string) $o->getContext()->getName());
            }

            if ($c instanceof PropertyContext && null !== ($s = $c->getHooks())) {
                $output[] = $this->colorType($this->escape($s));
            }

            if (null !== ($s = $c->getOperator())) {
                $output[] = $this->escape($s);
            }
        }

        $s = $o->getDisplayType();
        if ($c->isRef()) {
            $s = '&'.$s;
        }

        $s = $this->colorType($this->escape($s));

        if ($o instanceof InstanceValue && $this->shouldRenderObjectIds()) {
            $s .= '#'.$o->getSplObjectId();
        }

        $output[] = $s;

        if (null !== ($s = $o->getDisplaySize())) {
            $output[] = '('.$this->escape($s).')';
        }

        if (null !== ($s = $o->getDisplayValue())) {
            if (self::$strlen_max) {
                $s = Utils::truncateString($s, self::$strlen_max);
            }
            $output[] = $this->colorValue($this->escape($s));
        }

        return \str_repeat(' ', $c->getDepth() * $this->indent_width).\implode(' ', $output);
    }

    public function renderChildren(AbstractValue $o): string
    {
        $children = $o->getDisplayChildren();

        if (!$children) {
            if ($o instanceof ArrayValue) {
                return ' []';
            }

            return '';
        }

        if ($o instanceof ArrayValue) {
            $output = ' [';
        } elseif ($o instanceof InstanceValue) {
            $output = ' (';
        } else {
            $output = '';
        }

        $output .= PHP_EOL;
        foreach ($children as $child) {
            $output .= $this->render($child);
        }

        $indent = \str_repeat(' ', $o->getContext()->getDepth() * $this->indent_width);

        if ($o instanceof ArrayValue) {
            $output .= $indent.']';
        } elseif ($o instanceof InstanceValue) {
            $output .= $indent.')';
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
     */
    protected function getPlugin(array $plugins, AbstractValue $o): ?PluginInterface
    {
        if ($overlap = \array_intersect_key($o->getHints(), $plugins)) {
            $plugin = $plugins[\array_key_last($overlap)];

            if (!isset($this->plugin_objs[$plugin]) && \is_a($plugin, PluginInterface::class, true)) {
                $this->plugin_objs[$plugin] = new $plugin($this);
            }

            return $this->plugin_objs[$plugin];
        }

        return null;
    }
}
