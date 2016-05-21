<?php

namespace Kint\Renderer;

use Kint;
use Kint\Object;
use Kint\Renderer;

class Rich extends Renderer
{
    public $plugins = array(
        'microtime' => '\\Kint\\Renderer\\Plugin\\MicrotimeRich',
    );

    private static $been_run = false;
    private $modifiers;
    private $callee;
    private $mini_trace;
    private $previous_caller;

    public function __construct(array $names, $modifiers, array $callee, array $mini_trace, array $previous_caller)
    {
        $this->modifiers = $modifiers;
        $this->callee = $callee;
        $this->mini_trace = $mini_trace;
        $this->previous_caller = $previous_caller;
    }

    public function render(Object $o)
    {
        $children = $this->renderChildren($o);

        return '<dl>'.$this->renderHeader($o, (bool) strlen($children)).$children.'</dl>';
    }

    private function renderHeader(Object $o, $has_children)
    {
        $output = '<dt';

        if ($has_children) {
            $output .= ' class="kint-parent';

            if (Kint::$expandedByDefault) {
                $output .= ' kint-show';
            }

            $output .= '"';
        }

        $output .= '>';

        if ($o->depth > 0 && $ap = $o->renderAccessPath()) {
            $output .= '<span class="kint-access-path-trigger" title="Show access path">&rlarr;</span>';
        }

        if ($has_children) {
            $output .= '<span class="kint-popup-trigger" title="Open in new window">&rarr;</span><nav></nav>';
        }

        if (($s = $o->renderModifiers()) !== null) {
            $output .= '<var>'.$s.'</var> ';
        }

        if (($s = $o->renderName()) !== null) {
            $output .= '<dfn>'.$s.'</dfn> ';

            if ($s = $o->renderOperator()) {
                $output .= $s.' ';
            }
        }

        if (($s = $o->renderType()) !== null) {
            $output .= '<var>'.$s.'</var>';
        }

        if (($s = $o->renderSize()) !== null) {
            $output .= '('.$s.') ';
        }

        if (($s = $o->renderValueShort()) !== null) {
            $output .= $s;
        }

        if (!empty($ap)) {
            $output .= '<div class="access-path">'.$ap.'</div>';
        }

        $output .= '</dt>';

        return $output;
    }

    private function renderChildren(Object $o)
    {
        if ($o instanceof Object\Recursion) {
            return '<dd><dl><dt>*RECURSION*</td></dl></dd>';
        } elseif ($o instanceof Object\DepthLimit) {
            return '<dd><dl><dt>*DEPTH TOO GREAT*</td></dl></dd>';
        } elseif (!($reps = $o->getRepresentations())) {
            return '';
        }

        $contents = array();
        $tabs = array();

        foreach ($reps as $rep) {
            $result = $this->renderRepresentation($rep);
            if (Object\Blob::strlen($result)) {
                $contents[] = $result;
                $tabs[] = Object\Blob::escape($rep->getLabel());
            }
        }

        if (empty($tabs)) {
            return '';
        }

        $output = '<dd>';

        if ($o->getRepresentation('contents') !== $o->getDefaultRepresentation() || count($tabs) > 1) {
            $output .= '<ul class="kint-tabs">';

            foreach ($tabs as $i => $tab) {
                if ($i === 0) {
                    $output .= '<li class="kint-active-tab">';
                } else {
                    $output .= '<li>';
                }
                $output .= $tab.'</li>';
            }

            $output .= '</ul><ul>';

            foreach ($contents as $tab) {
                $output .= '<li>'.$tab.'</li>';
            }

            $output .= '</ul>';
        } else {
            $output .= reset($contents);
        }

        return $output.'</dd>';
    }

    private function renderRepresentation(Object\Representation $rep)
    {
        if ($output = $this->plugins_render($rep)) {
            return $output;
        }

        if (is_array($rep->contents)) {
            $output = '';
            foreach ($rep->contents as $obj) {
                $output .= $this->render($obj);
            }

            return $output;
        } elseif (is_string($rep->contents)) {
            if (Object\Blob::strlen($rep->contents) < \Kint::$maxStrLength) {
                return;
            } else {
                return '<pre>'.Object\Blob::escape($rep->contents).'</pre>';
            }
        }

        return;
    }

    public function preRender()
    {
        $output = '';

        if (!self::$been_run || strpos($this->modifiers, '@') !== false || strpos($this->modifiers, '-') !== false) {
            $baseDir = KINT_DIR.'/view/compiled/';

            if (!is_readable($cssFile = $baseDir.Kint::$theme.'.css')) {
                $cssFile = $baseDir.'original.css';
            }

            $output .= '<script class="-kint-js">'.file_get_contents($baseDir.'../base.js').'</script><style class="-kint-css">'.file_get_contents($cssFile)."</style>\n";

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
        if (!Kint::$displayCalledFrom) {
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
            $output .= '['.$caller.']';
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

    private static function ideLink($file, $line)
    {
        $shortenedPath = Kint::shortenPath($file);
        if (!Kint::$fileLinkFormat) {
            return $shortenedPath.':'.$line;
        }

        $ideLink = Kint::getIdeLink($file, $line);
        $class = (strpos($ideLink, 'http://') === 0) ? 'class="kint-ide-link" ' : '';

        return "<a {$class}href=\"{$ideLink}\">{$shortenedPath}:{$line}</a>";
    }
}
