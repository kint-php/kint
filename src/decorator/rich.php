<?php

class Kint_Decorators_Rich
{
    public static $firstRun = true;
    # make calls to Kint::dump() from different places in source coloured differently.
    private static $_usedColors = array();

    public static function decorate(kintVariableData $kintVar, $accessChain = null, $parentType = 'root', $context = null)
    {
        $output = '<dl>';

        $extendedPresent = $kintVar->extendedValue !== null || $kintVar->_alternatives !== null;

        if ($extendedPresent) {
            $class = 'kint-parent';
            if (Kint::$expandedByDefault) {
                $class .= ' kint-show';
            }
            $output .= '<dt class="'.$class.'">';
        } else {
            $output .= '<dt>';
        }

        if ($kintVar->access !== null && $parentType != 'this' && $parentType != 'self' && (strpos($kintVar->access, 'protected') !== false || strpos($kintVar->access, 'private') !== false)) {
            $parentType = null;
        }

        if ($parentType == 'this') {
            $parentType = 'object';
        }

        $thisChain = null;
        if ($parentType !== null) {
            if ($parentType == 'root' && empty($kintVar->name) && class_exists($kintVar->type)) {
                $name = $kintVar->type;
            } else {
                $name = trim($kintVar->name, '\'');
            }

            if ($parentType == 'root') {
                $accessChain = $name;
            } elseif ($parentType == 'object') {
                $accessChain .= '->'.$name;

                if (substr($name, 0, 12) === '__construct(') {
                    $accessChain = 'new '.$context.substr($name, 11);
                }

                $thisChain = $accessChain;
            } elseif ($parentType == 'array') {
                if ($context !== null) {
                    $name = (string) $context;
                }

                if (ctype_digit($name) && $name === (string) (int) $name) {
                    $accessChain .= '['.$name.']';
                } else {
                    $accessChain .= '[\''.$name.'\']';
                }

                $thisChain = $accessChain;
            } elseif ($parentType == 'static') {
                $accessChain = $context.'::'.$name;
                $thisChain = $accessChain;
            } elseif ($parentType == 'self') {
                $accessChain = 'self::'.$name;
                $thisChain = $accessChain;
            }

            if ($kintVar->type == 'array') {
                $parentType = 'array';
            } elseif (class_exists($kintVar->type)) {
                $parentType = 'object';
            }
        }

        if ($thisChain) {
            $output .= '<span class="kint-access-path-trigger" title="Show access path">&rlarr;</span>';
        }

        if ($extendedPresent) {
            $output .= '<span class="kint-popup-trigger" title="Open in new window">&rarr;</span><nav></nav>';
        }

        $output .= self::_drawHeader($kintVar).$kintVar->value;

        if ($thisChain) {
            $output .= '<div class="access-path">'.$thisChain.'</div>';
        }

        $output .= '</dt>';

        if ($extendedPresent) {
            $output .= '<dd>';
        }

        if (isset($kintVar->extendedValue)) {
            if (is_array($kintVar->extendedValue)) {
                foreach ($kintVar->extendedValue as $k => $v) {
                    $output .= self::decorate($v, $accessChain, $parentType, $v->name ? null : $k);
                }
            } elseif (is_string($kintVar->extendedValue)) {
                $output .= '<pre>'.$kintVar->extendedValue.'</pre>';
            } else {
                $output .= self::decorate($kintVar->extendedValue); //it's kint's container
            }
        } elseif (isset($kintVar->_alternatives)) {
            $output .= '<ul class="kint-tabs">';

            foreach ($kintVar->_alternatives as $k => $var) {
                $active = $k === 0 ? ' class="kint-active-tab"' : '';
                $output .= "<li{$active}>".self::_drawHeader($var, false).'</li>';
            }

            $output .= '</ul><ul>';

            foreach ($kintVar->_alternatives as $var) {
                $output .= '<li>';

                if (is_array($var->value)) {
                    foreach ($var->value as $v) {
                        $p = null;
                        $c = null;

                        if (in_array($var->type, array('Static class properties', 'Available methods', 'contents')) && class_exists($kintVar->type)) {
                            $p = $parentType;
                            $c = $kintVar->type;

                            if ($v->operator == '::' || strpos($v->access, 'static') !== false) {
                                if ($kintVar->name == '$this') {
                                    $p = 'self';
                                } else {
                                    $p = 'static';
                                }
                            } elseif ($kintVar->name == '$this') {
                                $p = 'this';
                            }
                        }

                        $output .= is_string($v)
                            ? '<pre>'.$v.'</pre>'
                            : self::decorate($v, $accessChain, $p, $c);
                    }
                } elseif (is_string($var->value)) {
                    $output .= '<pre>'.$var->value.'</pre>';
                } elseif (isset($var->value)) {
                    throw new Exception(
                        'Kint has encountered an error, '
                        .'please paste this report to https://github.com/raveren/kint/issues<br>'
                        .'Error encountered at '.basename(__FILE__).':'.__LINE__.'<br>'
                        .' variables: '
                        .htmlspecialchars(var_export($kintVar->_alternatives, true), ENT_QUOTES)
                    );
                }

                $output .= '</li>';
            }

            $output .= '</ul>';
        }
        if ($extendedPresent) {
            $output .= '</dd>';
        }

        $output .= '</dl>';

        return $output;
    }

    public static function decorateTrace($traceData)
    {
        $output = '<dl class="kint-trace">';

        foreach ($traceData as $i => $step) {
            $class = 'kint-parent';
            if (Kint::$expandedByDefault) {
                $class .= ' kint-show';
            }

            $output .= '<dt class="'.$class.'">'
                .'<b>'.($i + 1).'</b> '
                .'<nav></nav>'
                .'<var>';

            if (isset($step['file'])) {
                $output .= self::_ideLink($step['file'], $step['line']);
            } else {
                $output .= 'PHP internal call';
            }

            $output .= '</var>';

            $output .= $step['function'];

            if (isset($step['args'])) {
                $output .= '('.implode(', ', array_keys($step['args'])).')';
            }
            $output .= '</dt><dd>';
            $firstTab = ' class="kint-active-tab"';
            $output .= '<ul class="kint-tabs">';

            if (!empty($step['source'])) {
                $output .= "<li{$firstTab}>Source</li>";
                $firstTab = '';
            }

            if (!empty($step['args'])) {
                $output .= "<li{$firstTab}>Arguments</li>";
                $firstTab = '';
            }

            if (!empty($step['object'])) {
                kintParser::reset();
                $calleeDump = kintParser::factory($step['object']);

                $output .= "<li{$firstTab}>Callee object [{$calleeDump->type}]</li>";
            }

            $output .= '</ul><ul>';

            if (!empty($step['source'])) {
                $output .= "<li><pre class=\"kint-source\">{$step['source']}</pre></li>";
            }

            if (!empty($step['args'])) {
                $output .= '<li>';
                $j = 0;
                foreach ($step['args'] as $k => $arg) {
                    kintParser::reset();
                    if (isset($step['index']) && $step['index'] !== null) {
                        $output .= self::decorate(kintParser::factory($arg, $k), 'debug_backtrace()['.$step['index']."]['args']", 'array', $j);
                    } else {
                        $output .= self::decorate(kintParser::factory($arg, $k));
                    }
                    ++$j;
                }
                $output .= '</li>';
            }
            if (!empty($step['object'])) {
                $output .= '<li>';
                if (isset($step['index']) && $step['index'] !== null) {
                    $output .= self::decorate($calleeDump, 'debug_backtrace(true)['.$step['index'].']', 'array', 'object');
                } else {
                    $output .= self::decorate($calleeDump);
                }
                $output .= '</li>';
            }

            $output .= '</ul></dd>';
        }
        $output .= '</dl>';

        return $output;
    }

    /**
     * called for each dump, opens the html tag.
     *
     * @param array $callee caller information taken from debug backtrace
     *
     * @return string
     */
    public static function wrapStart()
    {
        return '<div class="kint">';
    }

    /**
     * closes Kint::_wrapStart() started html tags and displays callee information.
     *
     * @param array $callee     caller information taken from debug backtrace
     * @param array $miniTrace  full path to kint call
     * @param array $prevCaller previous caller information taken from debug backtrace
     *
     * @return string
     */
    public static function wrapEnd($callee, $miniTrace, $prevCaller)
    {
        if (!Kint::$displayCalledFrom) {
            return '</div>';
        }

        $callingFunction = '';
        $calleeInfo = '';
        $traceDisplay = '';
        if (isset($prevCaller['class'])) {
            $callingFunction = $prevCaller['class'];
        }
        if (isset($prevCaller['type'])) {
            $callingFunction .= $prevCaller['type'];
        }
        if (isset($prevCaller['function'])
            && !in_array($prevCaller['function'], array('include', 'include_once', 'require', 'require_once'))
        ) {
            $callingFunction .= $prevCaller['function'].'()';
        }
        $callingFunction and $callingFunction = " [{$callingFunction}]";

        if (isset($callee['file'])) {
            $calleeInfo .= 'Called from '.self::_ideLink($callee['file'], $callee['line']);
        }

        if (!empty($miniTrace)) {
            $traceDisplay = '<ol>';
            foreach ($miniTrace as $step) {
                $traceDisplay .= '<li>'.self::_ideLink($step['file'], $step['line']); // closing tag not required
                if (isset($step['function'])
                    && !in_array($step['function'], array('include', 'include_once', 'require', 'require_once'))
                ) {
                    $classString = ' [';
                    if (isset($step['class'])) {
                        $classString .= $step['class'];
                    }
                    if (isset($step['type'])) {
                        $classString .= $step['type'];
                    }
                    $classString .= $step['function'].'()]';
                    $traceDisplay .= $classString;
                }
            }
            $traceDisplay .= '</ol>';

            $calleeInfo = '<nav></nav>'.$calleeInfo;
        }

        return '<footer>'
        .'<span class="kint-popup-trigger" title="Open in new window">&rarr;</span> '
        ."{$calleeInfo}{$callingFunction}{$traceDisplay}"
        .'</footer></div>';
    }

    private static function _drawHeader(kintVariableData $kintVar, $verbose = true)
    {
        $output = '';
        if ($verbose) {
            if ($kintVar->access !== null) {
                $output .= '<var>'.$kintVar->access.'</var> ';
            }

            if ($kintVar->name !== null && $kintVar->name !== '') {
                $output .= '<dfn>'.kintParser::escape($kintVar->name).'</dfn> ';
            }

            if ($kintVar->operator !== null) {
                $output .= $kintVar->operator.' ';
            }
        }

        if ($kintVar->type !== null) {
            if ($verbose) {
                $output .= '<var>';
            }

            $output .= $kintVar->type;

            if ($verbose) {
                $output .= '</var>';
            } else {
                $output .= ' ';
            }
        }

        if ($kintVar->size !== null) {
            $output .= '('.$kintVar->size.') ';
        }

        return $output;
    }

    private static function _ideLink($file, $line)
    {
        $shortenedPath = Kint::shortenPath($file);
        if (!Kint::$fileLinkFormat) {
            return $shortenedPath.':'.$line;
        }

        $ideLink = Kint::getIdeLink($file, $line);
        $class = (strpos($ideLink, 'http://') === 0) ? 'class="kint-ide-link" ' : '';

        return "<a {$class}href=\"{$ideLink}\">{$shortenedPath}:{$line}</a>";
    }

    /**
     * produces css and js required for display. May be called multiple times, will only produce output once per
     * pageload or until `-` or `@` modifier is used.
     *
     * @return string
     */
    public static function init()
    {
        $baseDir = KINT_DIR.'/view/compiled/';

        if (!is_readable($cssFile = $baseDir.Kint::$theme.'.css')) {
            $cssFile = $baseDir.'original.css';
        }

        return
            '<script class="-kint-js">'.file_get_contents($baseDir.'kint.js').'</script>'
            .'<style class="-kint-css">'.file_get_contents($cssFile)."</style>\n";
    }
}
