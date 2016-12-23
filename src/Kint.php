<?php

class Kint
{
    /**
     * @var mixed Kint mode
     *
     * false: Disabled
     * true: Enabled, default mode selection
     * string: Manual mode selection
     */
    public static $enabled_mode = true;

    /**
     * Default mode.
     *
     * @var string
     */
    public static $mode_default = self::MODE_RICH;

    /**
     * Default mode in CLI with cli_detection on.
     *
     * @var string
     */
    public static $mode_default_cli = self::MODE_CLI;

    /**
     * @var bool Return output instead of echoing
     */
    public static $return;

    /**
     * @var string format of the link to the source file in trace entries. Use %f for file path, %l for line number.
     *
     * [!] EXAMPLE (works with for phpStorm and RemoteCall Plugin):
     *
     * Kint::$file_link_format = 'http://localhost:8091/?message=%f:%l';
     */
    public static $file_link_format = '';

    /**
     * @var bool whether to display where kint was called from
     */
    public static $display_called_from = true;

    /**
     * @var array base directories of your application that will be displayed instead of the full path. Keys are paths,
     *            values are replacement strings
     *
     * [!] EXAMPLE (for Kohana framework):
     *
     * Kint::$app_root_dirs = array(
     *      APPPATH => 'APPPATH',
     *      SYSPATH => 'SYSPATH',
     *      MODPATH => 'MODPATH',
     *      DOCROOT => 'DOCROOT',
     * );
     *
     * [!] EXAMPLE #2 (for a semi-universal approach)
     *
     * Kint::$app_root_dirs = array(
     *      realpath( __DIR__ . '/../../..' ) => 'ROOT', // go up as many levels as needed in the realpath() param
     * );
     */
    public static $app_root_dirs = array();

    /**
     * @var int max array/object levels to go deep, if zero no limits are applied
     */
    public static $max_depth = 7;

    /**
     * @var bool expand all trees by default for rich view
     */
    public static $expanded = false;

    /**
     * @var bool enable detection when Kint is command line. Formats output with whitespace only; does not HTML-escape it
     */
    public static $cli_detection = true;

    /**
     * @var bool in addition to above setting, enable detection when Kint is run in *UNIX* command line.
     *           Attempts to add coloring, but if seen as plain text, the color information is visible as gibberish
     */
    public static $cli_colors = true;

    /**
     * @var array Kint aliases. Add debug functions in Kint wrappers here to fix modifiers and backtraces
     */
    public static $aliases = array(
        array('Kint', 'dump'),
        array('Kint', 'trace'),
    );

    /**
     * @var array Kint_Renderer descendants. Add to array to extend.
     */
    public static $renderers = array(
        self::MODE_RICH => 'Kint_Renderer_Rich',
        self::MODE_PLAIN => 'Kint_Renderer_Plain',
        self::MODE_WHITESPACE => 'Kint_Renderer_Plain',
        self::MODE_CLI => 'Kint_Renderer_Plain',
    );

    const MODE_RICH = 'r';
    const MODE_WHITESPACE = 'w';
    const MODE_CLI = 'c';
    const MODE_PLAIN = 'p';

    /**
     * Stashes or sets all settings at once.
     *
     * @param array|null $settings Array of all settings to be set or null to set none
     *
     * @return array Current settings
     */
    public static function settings(array $settings = null)
    {
        static $keys = array(
            'aliases',
            'app_root_dirs',
            'cli_colors',
            'cli_detection',
            'display_called_from',
            'enabled_mode',
            'expanded',
            'file_link_format',
            'max_depth',
            'mode_default',
            'mode_default_cli',
            'renderers',
            'return',
        );

        $out = array();

        foreach ($keys as $key) {
            $out[$key] = self::$$key;
        }

        if ($settings !== null) {
            $in = array_intersect_key($settings, $out);
            foreach ($in as $key => $val) {
                self::$$key = $val;
            }
        }

        return $out;
    }

    /**
     * Prints a debug backtrace, same as Kint::dump(1).
     *
     * @param array $trace [OPTIONAL] you can pass your own trace, otherwise, `debug_backtrace` will be called
     *
     * @return mixed
     */
    public static function trace($trace = null)
    {
        if (!self::$enabled_mode) {
            return false;
        }

        if ($trace === null) {
            if (KINT_PHP525) {
                $trace = debug_backtrace(true);
            } else {
                $trace = debug_backtrace();
            }
        }

        return self::dump($trace);
    }

    /**
     * Dump information about variables, accepts any number of parameters, supports modifiers:.
     *
     *  clean up any output before kint and place the dump at the top of page:
     *   - Kint::dump()
     *  *****
     *  expand all nodes on display:
     *   ! Kint::dump()
     *  *****
     *  dump variables disregarding their depth:
     *   + Kint::dump()
     *  *****
     *  return output instead of displaying it:
     *
     *   @ Kint::dump()
     *  *****
     *  force output as plain text
     *   ~ Kint::dump()
     *
     * Modifiers are supported by all dump wrapper functions, including Kint::trace(). Space is optional.
     *
     * @param mixed $data
     *
     * @return void|string
     */
    public static function dump($data = null)
    {
        if (!self::$enabled_mode) {
            return false;
        }

        $stash = self::settings();

        list($names, $parameters, $modifiers, $callee, $caller, $mini_trace) = self::getCalleeInfo(
            defined('DEBUG_BACKTRACE_IGNORE_ARGS')
                ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
                : debug_backtrace()
        );

        // set mode for current run
        if (self::$enabled_mode === true) {
            self::$enabled_mode = self::$mode_default;
            if (PHP_SAPI === 'cli' && self::$cli_detection === true) {
                self::$enabled_mode = self::$mode_default_cli;
            }
        }

        if (in_array('~', $modifiers)) {
            self::$enabled_mode = self::MODE_WHITESPACE;
        }

        if (!array_key_exists(self::$enabled_mode, self::$renderers)) {
            $renderer = self::$renderers[self::MODE_PLAIN];
        } else {
            $renderer = self::$renderers[self::$enabled_mode];
        }

        // process modifiers: @, +, ! and -
        if (in_array('-', $modifiers)) {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
        if (in_array('!', $modifiers)) {
            self::$expanded = true;
        }
        if (in_array('+', $modifiers)) {
            self::$max_depth = false;
        }
        if (in_array('@', $modifiers)) {
            self::$return = true;
        }

        $renderer = new $renderer(array(
            'param_shortnames' => $names,
            'param_names' => $parameters,
            'modifiers' => $modifiers,
            'callee' => $callee,
            'caller' => $caller,
            'minitrace' => $mini_trace,
            'settings' => self::settings(),
            'stash' => $stash,
        ));

        $output = call_user_func(array($renderer, 'preRender'));

        $parser = new Kint_Parser(self::$max_depth, empty($caller['class']) ? null : $caller['class']);

        if ($names === array(null) && func_num_args() === 1 && $data === 1) { // Kint::dump(1) shorthand
            if (KINT_PHP525) {
                $trace = debug_backtrace(true);
            } else {
                $trace = debug_backtrace();
            }

            $trace = Kint_Parser_Plugin_Trace::trimTrace($trace);
            $lastframe = array_shift($trace);
            $tracename = $lastframe['function'].'(1)';
            if (isset($lastframe['class'], $lastframe['type'])) {
                $tracename = $lastframe['class'].$lastframe['type'].$tracename;
            }
            $tracebase = Kint_Object::blank($tracename, 'debug_backtrace()');

            if (empty($trace)) {
                $output .= call_user_func(array($renderer, 'render'), $tracebase->transplant(new Kint_Object_Trace()));
            } else {
                $output .= call_user_func(array($renderer, 'render'), $parser->parse($trace, $tracebase));
            }
        } else {
            $data = func_get_args();
            if ($data === array()) {
                $output .= call_user_func(array($renderer, 'render'), new Kint_Object_Nothing());
            }
            foreach ($data as $i => $argument) {
                $output .= call_user_func(
                    array($renderer, 'render'),
                    $parser->parse(
                        $argument,
                        Kint_Object::blank(
                            isset($names[$i]) ? $names[$i] : null,
                            isset($parameters[$i]) ? $parameters[$i] : null
                        )
                    )
                );
            }
        }

        $output .= call_user_func(array($renderer, 'postRender'));

        if (self::$return) {
            self::settings($stash);

            return $output;
        }

        self::settings($stash);
        echo $output;

        return false;
    }

    /**
     * generic path display callback, can be configured in app_root_dirs; purpose is
     * to show relevant path info and hide as much of the path as possible.
     *
     * @param string $file
     *
     * @return string
     */
    public static function shortenPath($file)
    {
        $file = str_replace('\\', '/', $file);
        $shortenedName = $file;
        $replaced = false;
        if (is_array(self::$app_root_dirs)) {
            foreach (self::$app_root_dirs as $path => $replaceString) {
                if (empty($path)) {
                    continue;
                }

                $path = str_replace('\\', '/', $path);

                if (strpos($file, $path) === 0) {
                    $shortenedName = $replaceString.substr($file, strlen($path));
                    $replaced = true;
                    break;
                }
            }
        }

        // fallback to find common path with Kint dir
        if (!$replaced) {
            $pathParts = explode('/', str_replace('\\', '/', dirname(dirname(__FILE__))));
            $fileParts = explode('/', $file);
            $i = 0;
            foreach ($fileParts as $i => $filePart) {
                if (!isset($pathParts[$i]) || $pathParts[$i] !== $filePart) {
                    break;
                }
            }

            $shortenedName = ($i ? '.../' : '').implode('/', array_slice($fileParts, $i));
        }

        return $shortenedName;
    }

    public static function getIdeLink($file, $line)
    {
        return str_replace(array('%f', '%l'), array($file, $line), self::$file_link_format);
    }

    /**
     * returns parameter names that the function was passed, as well as any predefined symbols before function
     * call (modifiers).
     *
     * @param array $trace
     *
     * @return array($names, $parameters, $modifier, $callee, $caller, $miniTrace)
     */
    private static function getCalleeInfo($trace)
    {
        $miniTrace = array();

        foreach ($trace as $index => $frame) {
            if ($frame['function'] === 'spl_autoload_call' && !isset($frame['object']) && !isset($frame['class'])) {
                continue;
            }

            $miniTrace[] = $frame;
        }

        $miniTrace = Kint_Parser_Plugin_Trace::trimTrace($miniTrace);
        $callee = reset($miniTrace);
        $caller = next($miniTrace);
        if (!$callee) {
            $callee = null;
        }
        if (!$caller) {
            $caller = null;
        }

        unset($miniTrace[0]);

        foreach ($miniTrace as $index => &$frame) {
            if (!isset($frame['file'], $frame['line'])) {
                unset($miniTrace[$index]);
            } else {
                unset($frame['object'], $frame['args']);
            }
        }

        $miniTrace = array_values($miniTrace);

        if (!isset($callee['file'], $callee['line']) || !is_readable($callee['file'])) {
            return array(null, null, array(), $callee, $caller, $miniTrace);
        }

        // open the file and read it up to the position where the function call expression ended
        $file = fopen($callee['file'], 'r');
        $line = 0;
        $source = '';
        while (($row = fgets($file)) !== false) {
            if (++$line > $callee['line']) {
                break;
            }
            $source .= $row;
        }
        fclose($file);
        $source = self::removeAllButCode($source);

        if (empty($callee['class'])) {
            $codePattern = $callee['function'];
        } else {
            if ($callee['type'] === '::') {
                $codePattern = $callee['class']."\x07*".$callee['type']."\x07*".$callee['function'];
            } else {
                /*if ( $callee['type'] === '->' )*/
                $codePattern = ".*\x07*".$callee['type']."\x07*".$callee['function'];
            }
        }

        // todo if more than one call in one line - not possible to determine variable names
        // todo does not recognize string concat
        // get the position of the last call to the function
        preg_match_all("
            [
            # beginning of statement
            [\x07{(]

            # search for modifiers (group 1)
            ([-+!@~]*)?

            # spaces
            \x07*

            # check if output is assigned to a variable (group 2) todo: does not detect concat
            (
                \\$[a-z0-9_]+ # variable
                \x07*\\.?=\x07*  # assignment
            )?

            # possibly a namespace symbol
            \\\\?

            # spaces again
            \x07*

            # main call to Kint
            ({$codePattern})

            # spaces everywhere
            \x07*

            # find the character where kint's opening bracket resides (group 3)
            (\\()

            ]ix",
            $source,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $modifiers = end($matches[1]);
        $assignment = end($matches[2]);
        $callToKint = end($matches[3]);
        $bracket = end($matches[4]);

        if (empty($callToKint)) {
            // if a wrapper is misconfigured, don't display the whole file as variable name
            return array(array(), array(), str_split((string) $modifiers), $callee, $caller, $miniTrace);
        }

        $modifiers = str_split((string) $modifiers[0]);
        if ($assignment[1] !== -1) {
            $modifiers[] = '@';
        }

        $paramsRaw = $paramsString = preg_replace("[\x07+]", ' ', substr($source, $bracket[1] + 1));
        // we now have a string like this:
        // <parameters passed>); <the rest of the last read line>

        // remove everything in brackets and quotes, we don't need nested statements nor literal strings which would
        // only complicate separating individual arguments
        $c = strlen($paramsString);
        $inString = $escaped = $openedBracket = $closingBracket = false;
        $i = 0;
        $paramStart = 0;
        $inBrackets = 0;
        $openedBrackets = array();
        $parameters = array();

        while ($i < $c) {
            $letter = $paramsString[$i];

            if (!$inString) {
                if ($letter === '\'' || $letter === '"') {
                    $inString = $letter;
                } elseif ($letter === '(' || $letter === '[') {
                    ++$inBrackets;
                    $openedBrackets[] = $openedBracket = $letter;
                    $closingBracket = $openedBracket === '(' ? ')' : ']';
                } elseif ($inBrackets && $letter === $closingBracket) {
                    --$inBrackets;
                    array_pop($openedBrackets);
                    $openedBracket = end($openedBrackets);
                    $closingBracket = $openedBracket === '(' ? ')' : ']';
                } elseif (!$inBrackets && $letter === ')') {
                    $paramsString = substr($paramsString, 0, $i);
                    break;
                } elseif (!$inBrackets && $letter === ',') {
                    $parameters[] = trim(substr($paramsRaw, $paramStart, $i - $paramStart));
                    $paramStart = $i + 1;
                }
            } elseif ($letter === $inString && !$escaped) {
                $inString = false;
            }

            // replace whatever was inside quotes or brackets with untypeable characters, we don't
            // need that info. We'll later replace the whole string with '...'
            if ($inBrackets > 0) {
                if ($inBrackets > 1 || $letter !== $openedBracket) {
                    $paramsString[$i] = "\x07";
                }
            }
            if ($inString) {
                if ($letter !== $inString || $escaped) {
                    $paramsString[$i] = "\x07";
                }
            }

            $escaped = !$escaped && ($letter === '\\');
            ++$i;
        }

        $final_arg = trim(substr($paramsRaw, $paramStart, $i - $paramStart));
        if ($final_arg) {
            $parameters[] = $final_arg;
        }

        // by now we have an un-nested arguments list, lets make it to an array for processing further
        $arguments = explode(',', preg_replace("[\x07+]", '...', $paramsString));

        // test each argument whether it was passed literary or was it an expression or a variable name
        $names = array();
        $blacklist = array('null', 'true', 'false', 'array(...)', 'array()', '"..."', '[...]', 'b"..."');
        foreach ($arguments as $argument) {
            $argument = trim($argument);

            if (is_numeric($argument)
                || in_array(str_replace("'", '"', strtolower($argument)), $blacklist, true)
            ) {
                $names[] = null;
            } else {
                $names[] = $argument;
            }
        }

        return array($names, $parameters, $modifiers, $callee, $caller, $miniTrace);
    }

    /**
     * removes comments and zaps whitespace & <?php tags from php code, makes for easier further parsing.
     *
     * @param string $source
     *
     * @return string
     */
    private static function removeAllButCode($source)
    {
        $commentTokens = array(
            T_COMMENT => true, T_INLINE_HTML => true, T_DOC_COMMENT => true,
        );
        $whiteSpaceTokens = array(
            T_WHITESPACE => true, T_CLOSE_TAG => true,
            T_OPEN_TAG => true, T_OPEN_TAG_WITH_ECHO => true,
        );

        $cleanedSource = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (isset($commentTokens[$token[0]])) {
                    continue;
                }

                if (isset($whiteSpaceTokens[$token[0]])) {
                    $token = "\x07";
                } else {
                    $token = $token[1];
                }
            } elseif ($token === ';') {
                $token = "\x07";
            }

            $cleanedSource .= $token;
        }

        return $cleanedSource;
    }
}
