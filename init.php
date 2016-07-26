<?php
/**
 * Kint is a zero-setup debugging tool to output information about variables and stack traces prettily and comfortably.
 *
 * https://github.com/raveren/kint
 */
if (defined('KINT_DIR')) {
    return;
}

if (version_compare(PHP_VERSION, '5.1') < 0) {
    return trigger_error('Kint 2.0 requires PHP 5.1 or higher', E_USER_ERROR);
}

define('KINT_DIR',  dirname(__FILE__));
define('KINT_PHP53', (version_compare(PHP_VERSION, '5.3') >= 0));

// Only preload classes if no autoloader specified
if (!class_exists('Kint', true)) {
    require_once dirname(__FILE__).'/src/Kint.php';

    // Data
    require_once dirname(__FILE__).'/src/Object.php';
    require_once dirname(__FILE__).'/src/Object/Nothing.php';
    require_once dirname(__FILE__).'/src/Object/Blob.php';
    require_once dirname(__FILE__).'/src/Object/Instance.php';
    require_once dirname(__FILE__).'/src/Object/Closure.php';
    require_once dirname(__FILE__).'/src/Object/Recursion.php';
    require_once dirname(__FILE__).'/src/Object/DepthLimit.php';
    require_once dirname(__FILE__).'/src/Object/Method.php';
    require_once dirname(__FILE__).'/src/Object/Parameter.php';
    require_once dirname(__FILE__).'/src/Object/Representation.php';
    require_once dirname(__FILE__).'/src/Object/Representation/Microtime.php';
    require_once dirname(__FILE__).'/src/Object/Representation/Docstring.php';
    require_once dirname(__FILE__).'/src/Object/Representation/Source.php';

    // Parsers
    require_once dirname(__FILE__).'/src/Parser.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/Callback.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/ClassMethods.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/ClassStatics.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Closure.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/Color.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/FsPath.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/Json.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Microtime.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/ObjectIterable.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/Smarty.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/SplFileInfo.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/SplObjectStorage.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/Timestamp.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/Xml.php';

    // Renderers
    require_once dirname(__FILE__).'/src/Renderer.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich.php';
    //~ require_once dirname(__FILE__).'/src/Renderer/Plain.php';
    //~ require_once dirname(__FILE__).'/src/Renderer/Js.php';
    require_once dirname(__FILE__).'/src/Renderer/Plugin.php';
    require_once dirname(__FILE__).'/src/Renderer/Plugin/MicrotimeRich.php';
    require_once dirname(__FILE__).'/src/Renderer/Plugin/DocstringRich.php';
    require_once dirname(__FILE__).'/src/Renderer/Plugin/SourceRich.php';
}

// Dynamic default settings
Kint::$fileLinkFormat = ini_get('xdebug.file_link_format');
if (isset($_SERVER['DOCUMENT_ROOT'])) {
    Kint::$appRootDirs = array($_SERVER['DOCUMENT_ROOT'] => '&lt;ROOT&gt;');
}

if (!function_exists('d')

) {
    /**
     * Alias of Kint::dump().
     *
     * @return string
     */
    function d()
    {
        return call_user_func_array(array('Kint', 'dump'), func_get_args());
    }

    Kint::$aliases[] = 'd';
}

if (!function_exists('dd')) {
    /**
     * Alias of Kint::dump()
     * [!!!] IMPORTANT: execution will halt after call to this function.
     *
     * @return string
     *
     * @deprecated
     */
    function dd()
    {
        if (!Kint::$enabledMode) {
            return '';
        }

        echo "<pre>Kint: dd() is being deprecated, please use ddd() instead</pre>\n";
        call_user_func_array(array('Kint', 'dump'), func_get_args());
        exit;
    }

    Kint::$aliases[] = 'dd';
}

if (!function_exists('ddd')) {
    /**
     * Alias of Kint::dump()
     * [!!!] IMPORTANT: execution will halt after call to this function.
     *
     * @return string
     */
    function ddd()
    {
        if (!Kint::$enabledMode) {
            return '';
        }

        call_user_func_array(array('Kint', 'dump'), func_get_args());
        exit;
    }

    Kint::$aliases[] = 'ddd';
}

if (!function_exists('de')) {
    /**
     * Alias of Kint::dump(), however the output is delayed until the end of the script.
     *
     * @see d();
     */
    function de()
    {
        $stash = Kint::settings();

        Kint::$delayedMode = true;

        $out = call_user_func_array(array('Kint', 'dump'), func_get_args());

        Kint::settings($stash);

        Kint::settings($stash);

        return $out;
    }

    Kint::$aliases[] = 'de';
}

if (!function_exists('s')) {
    /**
     * Alias of Kint::dump(), however the output is in plain htmlescaped text and some minor visibility enhancements
     * added. If run in CLI mode, output is pure whitespace.
     *
     * To force rendering mode without autodetecting anything:
     *
     *  Kint::$enabledMode = Kint::MODE_PLAIN;
     *  Kint::dump( $variable );
     *
     * @return string
     */
    function s()
    {
        if (!Kint::$enabledMode) {
            return '';
        }

        $stash = Kint::settings();

        if (Kint::$enabledMode !== Kint::MODE_WHITESPACE) {
            Kint::$enabledMode = Kint::MODE_PLAIN;
            if (PHP_SAPI === 'cli' && Kint::$cliDetection === true) {
                Kint::$enabledMode = Kint::MODE_CLI;
            }
        }

        $out = call_user_func_array(array('Kint', 'dump'), func_get_args());

        Kint::settings($stash);

        return $out;
    }

    Kint::$aliases[] = 's';
}

if (!function_exists('sd')) {
    /**
     * @see s()
     *
     * [!!!] IMPORTANT: execution will halt after call to this function
     *
     * @return string
     */
    function sd()
    {
        if (!Kint::$enabledMode) {
            return '';
        }

        if (Kint::$enabledMode !== Kint::MODE_WHITESPACE) {
            Kint::$enabledMode = Kint::MODE_PLAIN;
            if (PHP_SAPI === 'cli' && Kint::$cliDetection === true) {
                Kint::$enabledMode = Kint::MODE_CLI;
            }
        }

        call_user_func_array(array('Kint', 'dump'), func_get_args());
        exit;
    }

    Kint::$aliases[] = 'sd';
}

if (!function_exists('se')) {
    /**
     * @see s()
     * @see de()
     */
    function se()
    {
        if (!Kint::$enabledMode) {
            return '';
        }

        $stash = Kint::settings();

        Kint::$delayedMode = true;
        if (Kint::$enabledMode !== Kint::MODE_WHITESPACE) {
            Kint::$enabledMode = Kint::MODE_PLAIN;
            if (PHP_SAPI === 'cli' && Kint::$cliDetection === true) {
                Kint::$enabledMode = Kint::MODE_CLI;
            }
        }

        $out = call_user_func_array(array('Kint', 'dump'), func_get_args());

        Kint::settings($stash);

        return $out;
    }

    Kint::$aliases[] = 'se';
}

if (!function_exists('j')) {
    /**
     * Alias of Kint::dump(), however the output is dumped to the javascript console and
     * added to the global array `kintDump`. If run in CLI mode, output is pure whitespace.
     *
     * To force rendering mode without autodetecting anything:
     *
     *  Kint::$enabledMode = Kint::MODE_JS;
     *  Kint::dump( $variable );
     *
     * @return string
     */
    function j()
    {
        if (!Kint::$enabledMode) {
            return '';
        }

        $stash = Kint::settings();

        if (Kint::$enabledMode !== Kint::MODE_WHITESPACE) {
            Kint::$enabledMode = Kint::MODE_JS;
            if (PHP_SAPI === 'cli' && Kint::$cliDetection === true) {
                Kint::$enabledMode = Kint::MODE_CLI;
            }
        }

        $out = call_user_func_array(array('Kint', 'dump'), func_get_args());

        Kint::settings($stash);

        return $out;
    }

    Kint::$aliases[] = 'j';
}

if (!function_exists('jd')) {
    /**
     * @see j()
     *
     * [!!!] IMPORTANT: execution will halt after call to this function
     *
     * @return string
     */
    function jd()
    {
        if (!Kint::$enabledMode) {
            return '';
        }

        if (Kint::$enabledMode !== Kint::MODE_WHITESPACE) {
            Kint::$enabledMode = Kint::MODE_JS;
            if (PHP_SAPI === 'cli' && Kint::$cliDetection === true) {
                Kint::$enabledMode = Kint::MODE_CLI;
            }
        }

        call_user_func_array(array('Kint', 'dump'), func_get_args());
        exit;
    }

    Kint::$aliases[] = 'jd';
}

if (!function_exists('je')) {
    /**
     * @see j()
     * @see de()
     */
    function je()
    {
        if (!Kint::$enabledMode) {
            return '';
        }

        $stash = Kint::settings();

        Kint::$delayedMode = true;
        if (Kint::$enabledMode !== Kint::MODE_WHITESPACE) {
            Kint::$enabledMode = Kint::MODE_JS;
            if (PHP_SAPI === 'cli' && Kint::$cliDetection === true) {
                Kint::$enabledMode = Kint::MODE_CLI;
            }
        }

        $out = call_user_func_array(array('Kint', 'dump'), func_get_args());

        Kint::settings($stash);

        return $out;
    }

    Kint::$aliases[] = 'je';
}
