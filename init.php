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

define('KINT_DIR', dirname(__FILE__));
define('KINT_PHP53', (version_compare(PHP_VERSION, '5.3') >= 0));

// Only preload classes if no autoloader specified
if (!class_exists('Kint', true)) {
    require_once dirname(__FILE__).'/src/Kint.php';

    // Data
    require_once dirname(__FILE__).'/src/Object.php';
    require_once dirname(__FILE__).'/src/Object/Instance.php';
    require_once dirname(__FILE__).'/src/Object/Blob.php';
    require_once dirname(__FILE__).'/src/Object/Closure.php';
    require_once dirname(__FILE__).'/src/Object/Color.php';
    require_once dirname(__FILE__).'/src/Object/Method.php';
    require_once dirname(__FILE__).'/src/Object/Nothing.php';
    require_once dirname(__FILE__).'/src/Object/Parameter.php';
    require_once dirname(__FILE__).'/src/Object/Trace.php';
    require_once dirname(__FILE__).'/src/Object/TraceFrame.php';
    require_once dirname(__FILE__).'/src/Object/Representation.php';
    require_once dirname(__FILE__).'/src/Object/Representation/Color.php';
    require_once dirname(__FILE__).'/src/Object/Representation/Docstring.php';
    require_once dirname(__FILE__).'/src/Object/Representation/FsPath.php';
    require_once dirname(__FILE__).'/src/Object/Representation/Microtime.php';
    require_once dirname(__FILE__).'/src/Object/Representation/Source.php';

    // Parsers
    require_once dirname(__FILE__).'/src/Parser.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Blacklist.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/ClassMethods.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/ClassStatics.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Closure.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Color.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/FsPath.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Iterator.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Json.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Microtime.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/SimpleXMLElement.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Timestamp.php';
    require_once dirname(__FILE__).'/src/Parser/Plugin/Trace.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/Callback.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/Smarty.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/SplFileInfo.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/SplObjectStorage.php';
    //~ require_once dirname(__FILE__).'/src/Parser/Plugin/Xml.php';

    // Renderers
    require_once dirname(__FILE__).'/src/Renderer.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Plugin.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Blacklist.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Callable.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Closure.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Color.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/ColorDetails.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/DepthLimit.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Docstring.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/FsPath.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Microtime.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Nothing.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Recursion.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Source.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/Timestamp.php';
    require_once dirname(__FILE__).'/src/Renderer/Rich/TraceFrame.php';
    //~ require_once dirname(__FILE__).'/src/Renderer/Plain.php';
    require_once dirname(__FILE__).'/src/Renderer/Js.php';
}

// Dynamic default settings
Kint::$file_link_format = ini_get('xdebug.file_link_format');
if (isset($_SERVER['DOCUMENT_ROOT'])) {
    Kint::$app_root_dirs = array($_SERVER['DOCUMENT_ROOT'] => '<ROOT>');
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
        if (!Kint::$enabled_mode) {
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
        if (!Kint::$enabled_mode) {
            return '';
        }

        call_user_func_array(array('Kint', 'dump'), func_get_args());
        exit;
    }

    Kint::$aliases[] = 'ddd';
}

if (!function_exists('s')) {
    /**
     * Alias of Kint::dump(), however the output is in plain htmlescaped text and some minor visibility enhancements
     * added. If run in CLI mode, output is pure whitespace.
     *
     * To force rendering mode without autodetecting anything:
     *
     *  Kint::$enabled_mode = Kint::MODE_PLAIN;
     *  Kint::dump( $variable );
     *
     * @return string
     */
    function s()
    {
        if (!Kint::$enabled_mode) {
            return '';
        }

        $stash = Kint::settings();

        if (Kint::$enabled_mode !== Kint::MODE_WHITESPACE) {
            Kint::$enabled_mode = Kint::MODE_PLAIN;
            if (PHP_SAPI === 'cli' && Kint::$cli_detection === true) {
                Kint::$enabled_mode = Kint::MODE_CLI;
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
        if (!Kint::$enabled_mode) {
            return '';
        }

        if (Kint::$enabled_mode !== Kint::MODE_WHITESPACE) {
            Kint::$enabled_mode = Kint::MODE_PLAIN;
            if (PHP_SAPI === 'cli' && Kint::$cli_detection === true) {
                Kint::$enabled_mode = Kint::MODE_CLI;
            }
        }

        call_user_func_array(array('Kint', 'dump'), func_get_args());
        exit;
    }

    Kint::$aliases[] = 'sd';
}

if (!function_exists('j')) {
    /**
     * Alias of Kint::dump(), however the output is dumped to the javascript console and
     * added to the global array `kintDump`. If run in CLI mode, output is pure whitespace.
     *
     * To force rendering mode without autodetecting anything:
     *
     *  Kint::$enabled_mode = Kint::MODE_JS;
     *  Kint::dump( $variable );
     *
     * @return string
     */
    function j()
    {
        if (!Kint::$enabled_mode) {
            return '';
        }

        $stash = Kint::settings();

        if (Kint::$enabled_mode !== Kint::MODE_WHITESPACE) {
            Kint::$enabled_mode = Kint::MODE_JS;
            if (PHP_SAPI === 'cli' && Kint::$cli_detection === true) {
                Kint::$enabled_mode = Kint::MODE_CLI;
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
        if (!Kint::$enabled_mode) {
            return '';
        }

        if (Kint::$enabled_mode !== Kint::MODE_WHITESPACE) {
            Kint::$enabled_mode = Kint::MODE_JS;
            if (PHP_SAPI === 'cli' && Kint::$cli_detection === true) {
                Kint::$enabled_mode = Kint::MODE_CLI;
            }
        }

        call_user_func_array(array('Kint', 'dump'), func_get_args());
        exit;
    }

    Kint::$aliases[] = 'jd';
}
