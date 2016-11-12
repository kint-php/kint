<?php

class Kint_Renderer_Rich extends Kint_Renderer
{
    public static $object_renderers = array(
        'callable' => 'Kint_Renderer_Rich_Callable',
        'closure' => 'Kint_Renderer_Rich_Closure',
        'depth_limit' => 'Kint_Renderer_Rich_DepthLimit',
        'nothing' => 'Kint_Renderer_Rich_Nothing',
        'recursion' => 'Kint_Renderer_Rich_Recursion',
        'trace_frame' => 'Kint_Renderer_Rich_TraceFrame',
    );
    public static $representation_renderers = array(
        'docstring' => 'Kint_Renderer_Rich_Docstring',
        'fspath' => 'Kint_Renderer_Rich_FsPath',
        'microtime' => 'Kint_Renderer_Rich_Microtime',
        'source' => 'Kint_Renderer_Rich_Source',
    );

    /**
     * @var theme css file (Relative paths start at KINT_DIR/resources/compiled)
     */
    public static $theme = 'original.css';

    private static $been_run = false;
    private $modifiers;
    private $callee;
    private $mini_trace;
    private $previous_caller;

    public function __construct(array $names = null, array $parameters = null, $modifiers = null, array $callee = null, array $caller = null, array $mini_trace = array())
    {
        parent::__construct($names, $parameters, $modifiers, $callee, $caller, $mini_trace);

        $this->modifiers = $modifiers;
        $this->callee = $callee;
        $this->mini_trace = $mini_trace;
        $this->previous_caller = $caller;
    }

    public function render(Kint_Object $o)
    {
        if ($plugin = $this->getPlugin(self::$object_renderers, $o->hints)) {
            if ($output = $plugin->render($o)) {
                return $output;
            }
        }

        $children = $this->renderChildren($o);
        $header = self::renderHeaderWrapper($o, (bool) strlen($children), self::renderHeader($o));

        return '<dl>'.$header.$children.'</dl>';
    }

    public static function renderHeaderWrapper(Kint_Object $o, $has_children, $contents)
    {
        $open = '<dt';
        $close = '';

        if ($has_children) {
            $open .= ' class="kint-parent';

            if (Kint::$expanded) {
                $open .= ' kint-show';
            }

            $open .= '"';
        }

        $open .= '>';

        if ($o->depth > 0 && $ap = $o->getAccessPath()) {
            $open .= '<span class="kint-access-path-trigger" title="Show access path">&rlarr;</span>';
        }

        if ($has_children) {
            $open .= '<span class="kint-popup-trigger" title="Open in new window">&rarr;</span><nav></nav>';
        }

        if (!empty($ap)) {
            $close .= '<div class="access-path">'.Kint_Object_Blob::escape($ap).'</div>';
        }

        return $open.$contents.$close.'</dt>';
    }

    public static function renderHeader(Kint_Object $o)
    {
        $output = '';

        if (($s = $o->getModifiers()) !== null) {
            $output .= '<var>'.$s.'</var> ';
        }

        if (($s = $o->getName()) !== null) {
            $output .= '<dfn>'.Kint_Object_Blob::escape($s).'</dfn> ';

            if ($s = $o->getOperator()) {
                $output .= Kint_Object_Blob::escape($s).' ';
            }
        }

        if (($s = $o->getType()) !== null) {
            $output .= '<var>'.Kint_Object_Blob::escape($s).'</var>';
        }

        if (($s = $o->getSize()) !== null) {
            $output .= '('.$s.') ';
        }

        if (($s = $o->getValueShort()) !== null) {
            if (Kint_Object_Blob::strlen($s) > Kint::$max_str_length) {
                $s = substr($s, 0, Kint::$max_str_length).'...';
            }
            $output .= Kint_Object_Blob::escape($s);
        }

        return $output;
    }

    public function renderChildren(Kint_Object $o)
    {
        $contents = array();
        $tabs = array();

        foreach ($o->getRepresentations() as $rep) {
            $result = $this->renderRepresentation($o, $rep);
            if (Kint_Object_Blob::strlen($result)) {
                $contents[] = $result;
                $tabs[] = $rep;
            }
        }

        if (empty($tabs)) {
            return '';
        }

        $output = '<dd>';

        if (count($tabs) == 1 && $tabs[0]->labelIsImplicit()) {
            $output .= reset($contents);
        } else {
            $output .= '<ul class="kint-tabs">';

            foreach ($tabs as $i => $tab) {
                if ($i === 0) {
                    $output .= '<li class="kint-active-tab">';
                } else {
                    $output .= '<li>';
                }
                $output .= Kint_Object_Blob::escape($tab->getLabel()).'</li>';
            }

            $output .= '</ul><ul>';

            foreach ($contents as $tab) {
                $output .= '<li>'.$tab.'</li>';
            }

            $output .= '</ul>';
        }

        return $output.'</dd>';
    }

    private function renderRepresentation(Kint_Object $o, Kint_Object_Representation $rep)
    {
        if ($plugin = $this->getPlugin(self::$representation_renderers, $rep->hints)) {
            if ($output = $plugin->render($rep)) {
                return $output;
            }
        }

        if (is_array($rep->contents)) {
            $output = '';
            foreach ($rep->contents as $obj) {
                $output .= $this->render($obj);
            }

            return $output;
        } elseif (is_string($rep->contents)) {
            if ($o->value_representation === $rep && Kint_Object_Blob::strlen($rep->contents) < Kint::$max_str_length) {
                return;
            }

            return '<pre>'.Kint_Object_Blob::escape($rep->contents).'</pre>';
        } elseif ($rep->contents instanceof Kint_Object) {
            return $this->render($rep->contents);
        }

        return;
    }

    public function preRender()
    {
        $output = '';

        if (!self::$been_run || strpos($this->modifiers, '@') !== false || strpos($this->modifiers, '-') !== false) {
            $base_dir = KINT_DIR.'/resources/compiled/';

            if (self::$theme[0] == '/' && is_readable(self::$theme)) {
                $css_file = self::$theme;
            } elseif (is_readable($base_dir.self::$theme)) {
                $css_file = $base_dir.self::$theme;
            } else {
                $css_file = $base_dir.'original.css';
            }

            $output .= '<script class="-kint-js">'.file_get_contents($base_dir.'rich.js').'</script><style class="-kint-css">'.file_get_contents($css_file)."</style>\n";

            if (strpos($this->modifiers, '@') === false) {
                self::$been_run = true;
            } elseif (strpos($this->modifiers, '-') !== false) {
                self::$been_run = false;
            }
        }

        return $output.'<div class="kint">';
    }

    public function postRender()
    {
        if (!Kint::$display_called_from) {
            return '</div>';
        }

        $output = '<footer>';
        $output .= '<span class="kint-popup-trigger" title="Open in new window">&rarr;</span> ';

        if (isset($this->callee['file'])) {
            if (!empty($this->mini_trace)) {
                $output .= '<nav></nav>';
            }

            $output .= 'Called from '.self::ideLink($this->callee['file'], $this->callee['line']);
        }

        $caller = '';

        if (isset($this->previous_caller['class'])) {
            $caller .= $this->previous_caller['class'];
        }
        if (isset($this->previous_caller['type'])) {
            $caller .= $this->previous_caller['type'];
        }
        if (isset($this->previous_caller['function'])
            && !in_array($this->previous_caller['function'], array('include', 'include_once', 'require', 'require_once'))
        ) {
            $caller .= $this->previous_caller['function'].'()';
        }

        if ($caller) {
            $output .= ' ['.$caller.']';
        }

        if (!empty($this->mini_trace)) {
            $output .= '<ol>';
            foreach ($this->mini_trace as $step) {
                $output .= '<li>'.self::ideLink($step['file'], $step['line']); // closing tag not required
                if (isset($step['function'])
                    && !in_array($step['function'], array('include', 'include_once', 'require', 'require_once'))
                ) {
                    $output .= ' [';
                    if (isset($step['class'])) {
                        $output .= $step['class'];
                    }
                    if (isset($step['type'])) {
                        $output .= $step['type'];
                    }
                    $output .= $step['function'].'()]';
                }
            }
            $output .= '</ol>';
        }

        $output .= '</footer></div>';

        return $output;
    }

    private function getPlugin(array $plugins, array $hints)
    {
        if ($plugins = $this->matchPlugins($plugins, $hints)) {
            $plugin = end($plugins);
            return new $plugin($this);
        }
    }

    private static function ideLink($file, $line)
    {
        $shortenedPath = Kint_Object_Blob::escape(Kint::shortenPath($file));
        if (!Kint::$file_link_format) {
            return $shortenedPath.':'.$line;
        }

        $ideLink = Kint::getIdeLink($file, $line);
        $class = (strpos($ideLink, 'http://') === 0) ? 'class="kint-ide-link" ' : '';

        return "<a {$class}href=\"{$ideLink}\">{$shortenedPath}:{$line}</a>";
    }
}
