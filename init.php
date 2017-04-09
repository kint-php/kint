<?php
/**
 * Kint is a zero-setup debugging tool to output information about variables and stack traces prettily and comfortably.
 *
 * https://github.com/kint-php/kint
 */
if (defined('KINT_DIR')) {
    return;
}

if (version_compare(PHP_VERSION, '5.1.2') < 0) {
    return trigger_error('Kint 2.0 requires PHP 5.1.2 or higher', E_USER_ERROR);
}

define('KINT_DIR', dirname(__FILE__));
define('KINT_WIN', DIRECTORY_SEPARATOR !== '/');
define('KINT_PHP52', (version_compare(PHP_VERSION, '5.2') >= 0));
define('KINT_PHP523', (version_compare(PHP_VERSION, '5.2.3') >= 0));
define('KINT_PHP524', (version_compare(PHP_VERSION, '5.2.4') >= 0));
define('KINT_PHP525', (version_compare(PHP_VERSION, '5.2.5') >= 0));
define('KINT_PHP53', (version_compare(PHP_VERSION, '5.3') >= 0));
define('KINT_PHP70', (version_compare(PHP_VERSION, '7.0') >= 0));
define('KINT_PHP72', (version_compare(PHP_VERSION, '7.2') >= 0));

// Only preload classes if no autoloader specified
if (!class_exists('Kint', true)) {
    require_once KINT_DIR.'/src/Kint.php';

    // Data
    require_once KINT_DIR.'/src/Object.php';
    require_once KINT_DIR.'/src/Object/Instance.php';
    require_once KINT_DIR.'/src/Object/Blob.php';
    require_once KINT_DIR.'/src/Object/Closure.php';
    require_once KINT_DIR.'/src/Object/Color.php';
    require_once KINT_DIR.'/src/Object/Method.php';
    require_once KINT_DIR.'/src/Object/Nothing.php';
    require_once KINT_DIR.'/src/Object/Parameter.php';
    require_once KINT_DIR.'/src/Object/Resource.php';
    require_once KINT_DIR.'/src/Object/Stream.php';
    require_once KINT_DIR.'/src/Object/Trace.php';
    require_once KINT_DIR.'/src/Object/TraceFrame.php';
    require_once KINT_DIR.'/src/Object/Representation.php';
    require_once KINT_DIR.'/src/Object/Representation/Color.php';
    require_once KINT_DIR.'/src/Object/Representation/Docstring.php';
    require_once KINT_DIR.'/src/Object/Representation/Microtime.php';
    require_once KINT_DIR.'/src/Object/Representation/Source.php';
    require_once KINT_DIR.'/src/Object/Representation/SplFileInfo.php';

    // Parsers
    require_once KINT_DIR.'/src/Parser.php';
    require_once KINT_DIR.'/src/Parser/Plugin.php';
    require_once KINT_DIR.'/src/Parser/Base64.php';
    require_once KINT_DIR.'/src/Parser/Binary.php';
    require_once KINT_DIR.'/src/Parser/Blacklist.php';
    require_once KINT_DIR.'/src/Parser/ClassMethods.php';
    require_once KINT_DIR.'/src/Parser/ClassStatics.php';
    require_once KINT_DIR.'/src/Parser/Closure.php';
    require_once KINT_DIR.'/src/Parser/Color.php';
    require_once KINT_DIR.'/src/Parser/DOMIterator.php';
    require_once KINT_DIR.'/src/Parser/DOMNode.php';
    require_once KINT_DIR.'/src/Parser/FsPath.php';
    require_once KINT_DIR.'/src/Parser/Iterator.php';
    require_once KINT_DIR.'/src/Parser/Json.php';
    require_once KINT_DIR.'/src/Parser/Microtime.php';
    require_once KINT_DIR.'/src/Parser/Serialize.php';
    require_once KINT_DIR.'/src/Parser/SimpleXMLElement.php';
    require_once KINT_DIR.'/src/Parser/SplFileInfo.php';
    require_once KINT_DIR.'/src/Parser/SplObjectStorage.php';
    require_once KINT_DIR.'/src/Parser/Stream.php';
    require_once KINT_DIR.'/src/Parser/Table.php';
    require_once KINT_DIR.'/src/Parser/Timestamp.php';
    require_once KINT_DIR.'/src/Parser/ToString.php';
    require_once KINT_DIR.'/src/Parser/Trace.php';
    require_once KINT_DIR.'/src/Parser/Xml.php';

    // Renderers
    require_once KINT_DIR.'/src/Renderer.php';
    require_once KINT_DIR.'/src/Renderer/Text.php';
    require_once KINT_DIR.'/src/Renderer/Cli.php';
    require_once KINT_DIR.'/src/Renderer/Plain.php';
    require_once KINT_DIR.'/src/Renderer/Text/Plugin.php';
    require_once KINT_DIR.'/src/Renderer/Text/Blacklist.php';
    require_once KINT_DIR.'/src/Renderer/Text/DepthLimit.php';
    require_once KINT_DIR.'/src/Renderer/Text/Nothing.php';
    require_once KINT_DIR.'/src/Renderer/Text/Recursion.php';
    require_once KINT_DIR.'/src/Renderer/Text/Trace.php';
    require_once KINT_DIR.'/src/Renderer/Rich.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Plugin.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Binary.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Blacklist.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Callable.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Closure.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Color.php';
    require_once KINT_DIR.'/src/Renderer/Rich/ColorDetails.php';
    require_once KINT_DIR.'/src/Renderer/Rich/DepthLimit.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Docstring.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Microtime.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Nothing.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Recursion.php';
    require_once KINT_DIR.'/src/Renderer/Rich/SimpleXMLElement.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Source.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Table.php';
    require_once KINT_DIR.'/src/Renderer/Rich/Timestamp.php';
    require_once KINT_DIR.'/src/Renderer/Rich/TraceFrame.php';
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
        $args = func_get_args();

        return call_user_func_array(array('Kint', 'dump'), $args);
    }

    Kint::$aliases[] = 'd';
}

if (!function_exists('s')) {
    /**
     * Alias of Kint::dump(), however the output is in plain text.
     *
     * Alias of Kint::dump(), however the output is in plain htmlescaped text
     * with some minor visibility enhancements added.
     *
     * If run in CLI mode, output is not escaped.
     *
     * To force rendering mode without autodetecting anything:
     *
     * Kint::$enabled_mode = Kint::MODE_PLAIN;
     * Kint::dump( $variable );
     *
     * @return string
     */
    function s()
    {
        if (!Kint::$enabled_mode) {
            return 0;
        }

        $stash = Kint::settings();

        if (Kint::$enabled_mode !== Kint::MODE_TEXT) {
            Kint::$enabled_mode = Kint::MODE_PLAIN;
            if (PHP_SAPI === 'cli' && Kint::$cli_detection === true) {
                Kint::$enabled_mode = Kint::$mode_default_cli;
            }
        }

        $args = func_get_args();
        $out = call_user_func_array(array('Kint', 'dump'), $args);

        Kint::settings($stash);

        return $out;
    }

    Kint::$aliases[] = 's';
}
