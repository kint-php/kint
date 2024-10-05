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
use Kint\Renderer\Rich\PluginInterface;
use Kint\Renderer\Rich\TabPluginInterface;
use Kint\Renderer\Rich\ValuePluginInterface;
use Kint\Utils;
use Kint\Zval\BlobValue;
use Kint\Zval\Context\ClassDeclaredContext;
use Kint\Zval\Context\ContextInterface;
use Kint\Zval\Context\PropertyContext;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

/**
 * @psalm-import-type Encoding from BlobValue
 * @psalm-import-type PluginMap from AbstractRenderer
 */
class RichRenderer extends AbstractRenderer
{
    use AssetRendererTrait;

    /**
     * RichRenderer value plugins should implement ValuePluginInterface.
     *
     * @psalm-var class-string<ValuePluginInterface>[]
     */
    public static array $value_plugins = [
        'array_limit' => Rich\ArrayLimitPlugin::class,
        'blacklist' => Rich\BlacklistPlugin::class,
        'callable' => Rich\CallablePlugin::class,
        'color' => Rich\ColorPlugin::class,
        'depth_limit' => Rich\DepthLimitPlugin::class,
        'recursion' => Rich\RecursionPlugin::class,
        'simplexml_element' => Rich\SimpleXMLElementPlugin::class,
        'trace_frame' => Rich\TraceFramePlugin::class,
        'filesize' => Rich\FilesizePlugin::class,
    ];

    /**
     * RichRenderer tab plugins should implement TabPluginInterface.
     *
     * @psalm-var class-string<TabPluginInterface>[]
     */
    public static array $tab_plugins = [
        'binary' => Rich\BinaryPlugin::class,
        'color' => Rich\ColorPlugin::class,
        'callable_definition' => Rich\CallableDefinitionPlugin::class,
        'microtime' => Rich\MicrotimePlugin::class,
        'source' => Rich\SourcePlugin::class,
        'table' => Rich\TablePlugin::class,
        'timestamp' => Rich\TimestampPlugin::class,
        'profiling' => Rich\ProfilePlugin::class,
    ];

    public static array $pre_render_sources = [
        'script' => [
            [self::class, 'renderJs'],
        ],
        'style' => [
            [self::class, 'renderCss'],
        ],
        'raw' => [],
    ];

    /**
     * The maximum length of a string before it is truncated.
     *
     * Falsey to disable
     */
    public static int $strlen_max = 80;

    /**
     * Timestamp to print in footer in date() format.
     */
    public static ?string $timestamp = null;

    /**
     * Whether or not to render access paths.
     *
     * Access paths can become incredibly heavy with very deep and wide
     * structures. Given mostly public variables it will typically make
     * up one quarter of the output HTML size.
     *
     * If this is an unacceptably large amount and your browser is groaning
     * under the weight of the access paths - your first order of buisiness
     * should be to get a new browser. Failing that, use this to turn them off.
     */
    public static bool $access_paths = true;

    /**
     * Assume types and sizes don't need to be escaped.
     *
     * Turn this off if you use anything but ascii in your class names,
     * but it'll cause a slowdown of around 10%
     */
    public static bool $escape_types = false;

    /**
     * Move all dumps to a folder at the bottom of the body.
     */
    public static bool $folder = false;

    public static bool $needs_pre_render = true;
    public static bool $always_pre_render = false;

    protected array $plugin_objs = [];
    protected bool $expand = false;
    protected bool $force_pre_render = false;
    protected bool $use_folder = false;

    public function __construct()
    {
        parent::__construct();
        self::$theme ??= 'original.css';
        $this->use_folder = self::$folder;
        $this->force_pre_render = self::$always_pre_render;
    }

    public function setCallInfo(array $info): void
    {
        parent::setCallInfo($info);

        if (\in_array('!', $this->call_info['modifiers'], true)) {
            $this->expand = true;
            $this->use_folder = false;
        }

        if (\in_array('@', $this->call_info['modifiers'], true)) {
            $this->force_pre_render = true;
        }
    }

    public function setStatics(array $statics): void
    {
        parent::setStatics($statics);

        if (!empty($statics['expanded'])) {
            $this->expand = true;
        }

        if (!empty($statics['return'])) {
            $this->force_pre_render = true;
        }
    }

    public function shouldPreRender(): bool
    {
        return $this->force_pre_render || self::$needs_pre_render;
    }

    public function render(Value $o): string
    {
        $render_spl_ids_stash = $this->render_spl_ids;

        if ($this->render_spl_ids && isset($o->hints['omit_spl_id'])) {
            $this->render_spl_ids = false;
        }

        if (($plugin = $this->getPlugin(self::$value_plugins, $o->hints)) && $plugin instanceof ValuePluginInterface) {
            $output = $plugin->renderValue($o);
            if (null !== $output && \strlen($output)) {
                if (!$this->render_spl_ids && $render_spl_ids_stash) {
                    $this->render_spl_ids = true;
                }

                return $output;
            }
        }

        $children = $this->renderChildren($o);
        $header = $this->renderHeaderWrapper($o->getContext(), (bool) \strlen($children), $this->renderHeader($o));

        if (!$this->render_spl_ids && $render_spl_ids_stash) {
            $this->render_spl_ids = true;
        }

        return '<dl>'.$header.$children.'</dl>';
    }

    public function renderHeaderWrapper(ContextInterface $c, bool $has_children, string $contents): string
    {
        $out = '<dt';

        if ($has_children) {
            $out .= ' class="kint-parent';

            if ($this->expand) {
                $out .= ' kint-show';
            }

            $out .= '"';
        }

        $out .= '>';

        if (self::$access_paths && $c->getDepth() > 0 && null !== ($ap = $c->getAccessPath())) {
            $out .= '<span class="kint-access-path-trigger" title="Show access path">&rlarr;</span>';
        }

        if ($has_children) {
            if (0 === $c->getDepth()) {
                if (!$this->use_folder) {
                    $out .= '<span class="kint-folder-trigger" title="Move to folder">&mapstodown;</span>';
                }
                $out .= '<span class="kint-search-trigger" title="Show search box">&telrec;</span>';
                $out .= '<input type="text" class="kint-search" value="">';
            }

            $out .= '<nav></nav>';
        }

        $out .= $contents;

        if (!empty($ap)) {
            $out .= '<div class="access-path">'.$this->escape($ap).'</div>';
        }

        return $out.'</dt>';
    }

    public function renderHeader(Value $o): string
    {
        $c = $o->getContext();

        $output = '';

        if ($c instanceof ClassDeclaredContext) {
            $output .= '<var>'.$c->getModifiers().'</var> ';
        }

        $output .= '<dfn>'.$this->escape($o->getDisplayName()).'</dfn> ';

        if ($c instanceof PropertyContext && null !== ($s = $c->getHooks())) {
            $output .= '<var>'.$this->escape($s).'</var> ';
        }

        if (null !== ($s = $c->getOperator())) {
            $output .= $this->escape($s, 'ASCII').' ';
        }

        if (null !== ($s = $o->getType())) {
            if (self::$escape_types) {
                $s = $this->escape($s);
            }

            if ($c->isRef()) {
                $s = '&amp;'.$s;
            }

            $output .= '<var>'.$s.'</var>';

            if ($o instanceof InstanceValue && $this->shouldRenderObjectIds()) {
                $output .= '#'.$o->spl_object_id;
            }

            $output .= ' ';
        }

        if (null !== ($s = $o->getSize())) {
            if (self::$escape_types) {
                $s = $this->escape($s);
            }
            $output .= '('.$s.') ';
        }

        if (null !== ($s = $o->getValueShort())) {
            $s = \preg_replace('/\\s+/', ' ', $s);

            if (self::$strlen_max) {
                $s = Utils::truncateString($s, self::$strlen_max);
            }

            $output .= $this->escape($s);
        }

        return \trim($output);
    }

    public function renderChildren(Value $o): string
    {
        $contents = [];
        $tabs = [];

        foreach ($o->getRepresentations() as $rep) {
            $result = $this->renderTab($o, $rep);
            if (\strlen($result)) {
                $contents[] = $result;
                $tabs[] = $rep;
            }
        }

        if (empty($tabs)) {
            return '';
        }

        $output = '<dd>';

        if (1 === \count($tabs) && $tabs[0]->labelIsImplicit()) {
            $output .= \reset($contents);
        } else {
            $output .= '<ul class="kint-tabs">';

            foreach ($tabs as $i => $tab) {
                if (0 === $i) {
                    $output .= '<li class="kint-active-tab">';
                } else {
                    $output .= '<li>';
                }

                $output .= $this->escape($tab->getLabel()).'</li>';
            }

            $output .= '</ul><ul class="kint-tab-contents">';

            foreach ($contents as $i => $tab) {
                if (0 === $i) {
                    $output .= '<li class="kint-show">';
                } else {
                    $output .= '<li>';
                }

                $output .= $tab.'</li>';
            }

            $output .= '</ul>';
        }

        return $output.'</dd>';
    }

    public function preRender(): string
    {
        $output = '';

        if ($this->shouldPreRender()) {
            foreach (self::$pre_render_sources as $type => $values) {
                $contents = '';
                foreach ($values as $v) {
                    $contents .= \call_user_func($v, $this);
                }

                if (!\strlen($contents)) {
                    continue;
                }

                switch ($type) {
                    case 'script':
                        $output .= '<script class="kint-rich-script"';
                        if (null !== self::$js_nonce) {
                            $output .= ' nonce="'.\htmlspecialchars(self::$js_nonce).'"';
                        }
                        $output .= '>'.$contents.'</script>';
                        break;
                    case 'style':
                        $output .= '<style class="kint-rich-style"';
                        if (null !== self::$css_nonce) {
                            $output .= ' nonce="'.\htmlspecialchars(self::$css_nonce).'"';
                        }
                        $output .= '>'.$contents.'</style>';
                        break;
                    default:
                        $output .= $contents;
                }
            }

            // Don't pre-render on every dump
            if (!$this->force_pre_render) {
                self::$needs_pre_render = false;
            }
        }

        $output .= '<div class="kint-rich';

        if ($this->use_folder) {
            $output .= ' kint-file';
        }

        $output .= '">';

        return $output;
    }

    public function postRender(): string
    {
        if (!$this->show_trace) {
            return '</div>';
        }

        $output = '<footer>';

        if (!$this->use_folder) {
            $output .= '<span class="kint-folder-trigger" title="Move to folder">&mapstodown;</span>';
        }

        if (!empty($this->call_info['trace']) && \count($this->call_info['trace']) > 1) {
            $output .= '<nav></nav>';
        }

        $output .= $this->calledFrom();

        if (!empty($this->call_info['trace']) && \count($this->call_info['trace']) > 1) {
            $output .= '<ol>';
            foreach ($this->call_info['trace'] as $index => $step) {
                if (!$index) {
                    continue;
                }

                $output .= '<li>'.$this->ideLink($step['file'], $step['line']); // closing tag not required
                if (isset($step['function']) &&
                    !\in_array($step['function'], ['include', 'include_once', 'require', 'require_once'], true)
                ) {
                    $output .= ' [';
                    $output .= $step['class'] ?? '';
                    $output .= $step['type'] ?? '';
                    $output .= $step['function'].'()]';
                }
            }
            $output .= '</ol>';
        }

        $output .= '</footer></div>';

        return $output;
    }

    /**
     * @psalm-param Encoding $encoding
     */
    public function escape(string $string, $encoding = false): string
    {
        if (false === $encoding) {
            $encoding = BlobValue::detectEncoding($string);
        }

        $original_encoding = $encoding;

        if (false === $encoding || 'ASCII' === $encoding) {
            $encoding = 'UTF-8';
        }

        $string = \htmlspecialchars($string, ENT_NOQUOTES, $encoding);

        // this call converts all non-ASCII characters into numeirc htmlentities
        if (\function_exists('mb_encode_numericentity') && 'ASCII' !== $original_encoding) {
            $string = \mb_encode_numericentity($string, [0x80, 0xFFFF, 0, 0xFFFF], $encoding);
        }

        return $string;
    }

    public function ideLink(string $file, int $line): string
    {
        $path = $this->escape(Kint::shortenPath($file)).':'.$line;
        $ideLink = Kint::getIdeLink($file, $line);

        if (!$ideLink) {
            return $path;
        }

        return '<a href="'.$this->escape($ideLink).'">'.$path.'</a>';
    }

    protected function calledFrom(): string
    {
        $output = '';

        if (isset($this->call_info['callee']['file'])) {
            $output .= ' '.$this->ideLink(
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

        if ('' !== $output) {
            $output = 'Called from'.$output;
        }

        if (null !== self::$timestamp) {
            $output .= ' '.\date(self::$timestamp);
        }

        return $output;
    }

    protected function renderTab(Value $o, Representation $rep): string
    {
        if (($plugin = $this->getPlugin(self::$tab_plugins, $rep->hints)) && $plugin instanceof TabPluginInterface) {
            $output = $plugin->renderTab($rep);
            if (null !== $output && \strlen($output)) {
                return $output;
            }
        }

        if (\is_array($rep->contents)) {
            $output = '';

            foreach ($rep->contents as $obj) {
                $output .= $this->render($obj);
            }

            return $output;
        }

        if (\is_string($rep->contents)) {
            $show_contents = false;

            // If it is the value representation of a string and its whitespace
            // was truncated in the header, always display the full string
            if ('string' !== $o->type || $o->value !== $rep) {
                $show_contents = true;
            } else {
                if (\preg_match('/(:?[\\r\\n\\t\\f\\v]| {2})/', $rep->contents)) {
                    $show_contents = true;
                } elseif (self::$strlen_max && null !== ($vs = $o->getValueShort()) && BlobValue::strlen($vs) > self::$strlen_max) {
                    $show_contents = true;
                }

                if (empty($o->encoding)) {
                    $show_contents = false;
                }
            }

            if ($show_contents) {
                return '<pre>'.$this->escape($rep->contents)."\n</pre>";
            }
        }

        if ($rep->contents instanceof Value) {
            return $this->render($rep->contents);
        }

        return '';
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
