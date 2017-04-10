<?php

class Kint_SourceParser
{
    private static $ignore = array(
        T_CLOSE_TAG => true,
        T_COMMENT => true,
        T_DOC_COMMENT => true,
        T_INLINE_HTML => true,
        T_OPEN_TAG => true,
        T_OPEN_TAG_WITH_ECHO => true,
        T_WHITESPACE => true,
    );

    /**
     * Things we need to do specially for operator tokens:
     * - Refuse to strip spaces around them
     * - Wrap the access path in parentheses if there
     *   are any of these in the final short parameter.
     */
    private static $operator = array(
        T_AND_EQUAL => true,
        T_BOOLEAN_AND => true,
        T_BOOLEAN_OR => true,
        T_ARRAY_CAST => true,
        T_BOOL_CAST => true,
        T_CLONE => true,
        T_CONCAT_EQUAL => true,
        T_DEC => true,
        T_DIV_EQUAL => true,
        T_DOUBLE_CAST => true,
        T_INC => true,
        T_INCLUDE => true,
        T_INCLUDE_ONCE => true,
        T_INSTANCEOF => true,
        T_INT_CAST => true,
        T_IS_EQUAL => true,
        T_IS_GREATER_OR_EQUAL => true,
        T_IS_IDENTICAL => true,
        T_IS_NOT_EQUAL => true,
        T_IS_NOT_IDENTICAL => true,
        T_IS_SMALLER_OR_EQUAL => true,
        T_LOGICAL_AND => true,
        T_LOGICAL_OR => true,
        T_LOGICAL_XOR => true,
        T_MINUS_EQUAL => true,
        T_MOD_EQUAL => true,
        T_MUL_EQUAL => true,
        T_NEW => true,
        T_OBJECT_CAST => true,
        T_OR_EQUAL => true,
        T_PLUS_EQUAL => true,
        T_REQUIRE => true,
        T_REQUIRE_ONCE => true,
        T_SL => true,
        T_SL_EQUAL => true,
        T_SR => true,
        T_SR_EQUAL => true,
        T_STRING_CAST => true,
        T_UNSET_CAST => true,
        T_XOR_EQUAL => true,
        '!' => true,
        '%' => true,
        '&' => true,
        '*' => true,
        '+' => true,
        '-' => true,
        '.' => true,
        '/' => true,
        ':' => true,
        '<' => true,
        '=' => true,
        '>' => true,
        '?' => true,
        '^' => true,
        '|' => true,
        '~' => true,
    );

    private static $strip = array(
        '(' => true,
        ')' => true,
        '[' => true,
        ']' => true,
        '{' => true,
        '}' => true,
        T_OBJECT_OPERATOR => true,
        T_DOUBLE_COLON => true,
    );

    public static function getFunctionCalls($source, $line, $function)
    {
        $tokens = token_get_all($source);
        $cursor = 1;
        $function_calls = array();

        if (is_array($function)) {
            $class = explode('\\', $function[0]);
            $function = array(strtolower(end($class)), strtolower($function[1]));
        } else {
            $function = strtolower($function);
        }

        // Loop through tokens
        foreach ($tokens as $index => $token) {
            if (is_array($token)) {
                // Count newlines for line number instead of using
                // $token[2] since it's not available until 5.2.2
                // Also note that certain situations (String tokens after whitespace)
                // may not have the correct line number unless you do this manually
                $cursor += substr_count($token[1], "\n");
                if ($cursor > $line) {
                    break;
                }

                // If it's the function we're looking for
                if ($token[0] === T_STRING && $cursor <= $line && self::tokenIsFunction($tokens, $index, $function)) {
                    $inner_cursor = $cursor;
                    $params = self::getFunctionParameters($tokens, $index, $inner_cursor);

                    if ($params && $inner_cursor >= $line) {
                        $modifiers = self::getFunctionModifiers($tokens, $index);
                        $parameters = array();

                        foreach ($params as $param) {
                            $name = self::tokensFormatted($param['short']);
                            $expression = false;
                            foreach ($name as $token) {
                                if (self::tokenIsOperator($token)) {
                                    $expression = true;
                                    break;
                                }
                            }

                            $parameters[] = array(
                                'name' => self::tokensToString($name),
                                'path' => self::tokensToString(self::tokensTrim($param['full'])),
                                'expression' => $expression,
                            );
                        }

                        $function_calls[] = array(
                            'parameters' => $parameters,
                            'modifiers' => $modifiers,
                        );
                    }
                }
            }
        }

        return $function_calls;
    }

    private static function tokenIsFunction(array $tokens, $index, $function)
    {
        if (self::nextRealToken($tokens, $index) !== '(') {
            return false;
        }

        if (!is_array($tokens[$index])) {
            return false;
        }

        $last = self::realTokenIndex($tokens, $index, -1);

        if (is_string($function)) {
            if ($tokens[$index][0] !== T_STRING || strtolower($tokens[$index][1]) !== $function) {
                return false;
            }

            if ($last && in_array($tokens[$last][0], array(T_DOUBLE_COLON, T_OBJECT_OPERATOR))) {
                return false;
            }
        } elseif (is_array($function)) {
            if ($tokens[$index][0] !== T_STRING || strtolower($tokens[$index][1]) !== $function[1]) {
                return false;
            }

            if (!$last || $tokens[$last][0] !== T_DOUBLE_COLON) {
                return false;
            }

            $class = self::realTokenIndex($tokens, $last, -1);

            if (!$class || $tokens[$class][0] !== T_STRING || strtolower($tokens[$class][1]) !== $function[0]) {
                return false;
            }
        }

        return true;
    }

    private static function getFunctionModifiers(array $tokens, $index)
    {
        static $modifiers = array(
            '!',
            '@',
            '~',
            '+',
            '-',
        );

        $ret = array();
        --$index;

        while (isset($tokens[$index])) {
            if (self::tokenIsIgnored($tokens[$index])) {
                --$index;
                continue;
            } elseif (is_array($tokens[$index]) && empty($ret) && in_array($tokens[$index][0], array(T_DOUBLE_COLON, T_NS_SEPARATOR, T_STRING))) {
                --$index;
                continue;
            } elseif (is_string($tokens[$index]) && in_array($tokens[$index], $modifiers)) {
                $ret[] = $tokens[$index];
                --$index;
                continue;
            } else {
                break;
            }
        }

        return $ret;
    }

    private static function getFunctionParameters(array $tokens, $index, &$cursor)
    {
        static $up = array(
            '(' => true,
            '[' => true,
            '{' => true,
            T_CURLY_OPEN => true,
            T_DOLLAR_OPEN_CURLY_BRACES => true,
            T_STRING_VARNAME => true,
        );
        static $down = array(
            ')' => true,
            ']' => true,
            '}' => true,
        );

        $depth = 0; // The depth respective to the function call
        $offset = 1; // The offset from the function call
        $instring = false; // Whether we're in a string or not
        $parameters = array(); // All our collected parameters
        $shortparam = array(); // The short version of the parameter
        $param_start = 1; // The distance to the start of the parameter

        // Loop through the following tokens until the function call ends
        while (isset($tokens[$index + $offset])) {
            $token = $tokens[$index + $offset];

            // Ensure that the $cursor is correct and that
            // $token is either a T_ constant or a string
            if (is_array($token)) {
                $cursor += substr_count($token[1], "\n");
            }

            if (!self::tokenIsIgnored($token) && !isset($down[$token[0]])) {
                $realtokens = true;
            }

            // If it's a token that makes us to up a level, increase the depth
            if (isset($up[$token[0]])) {
                // If this is the first paren set the start of the param to just after it
                if ($depth === 0) {
                    $param_start = $offset + 1;
                } elseif ($depth === 1) {
                    $shortparam[] = $token;
                    $realtokens = false;
                }

                ++$depth;
            } elseif (isset($down[$token[0]])) {
                --$depth;

                // If this brings us down to the parameter level, and we've had
                // real tokens since going up, fill the $shortparam with an ellipsis
                if ($depth === 1) {
                    if ($realtokens) {
                        $shortparam[] = '...';
                    }
                    $shortparam[] = $token;
                }
            } elseif ($token[0] === '"') {
                // Strings use the same symbol for up and down, but we can
                // only ever be inside one string, so just use a bool for that
                if ($instring) {
                    --$depth;
                    if ($depth === 1) {
                        $shortparam[] = '...';
                    }
                } else {
                    ++$depth;
                }

                $instring = !$instring;

                $shortparam[] = '"';
            } elseif ($depth === 1) {
                if ($token[0] === ',') {
                    $parameters[] = array(
                        'full' => array_slice($tokens, $index + $param_start, $offset - $param_start),
                        'short' => $shortparam,
                    );
                    $shortparam = array();
                    $param_start = $offset + 1;
                } elseif ($token[0] === T_CONSTANT_ENCAPSED_STRING && strlen($token[1]) > 2) {
                    $shortparam[] = $token[1][0].'...'.$token[1][0];
                } else {
                    $shortparam[] = $token;
                }
            }

            // Depth has dropped to 0 (So we've hit the closing paren)
            if ($depth <= 0) {
                $parameters[] = array(
                    'full' => array_slice($tokens, $index + $param_start, $offset - $param_start),
                    'short' => $shortparam,
                );

                return $parameters;
            }

            ++$offset;
        }

        return null;
    }

    private static function nextRealToken(array $tokens, $index)
    {
        return $tokens[self::realTokenIndex($tokens, $index, 1)];
    }

    private static function lastRealToken(array $tokens, $index)
    {
        return $tokens[self::realTokenIndex($tokens, $index, -1)];
    }

    private static function realTokenIndex(array $tokens, $index, $direction)
    {
        $index += $direction;

        while (isset($tokens[$index])) {
            if (!self::tokenIsIgnored($tokens[$index])) {
                return $index;
            }

            $index += $direction;
        }

        return null;
    }

    private static function tokenIs($token, $compare)
    {
        if (is_array($token)) {
            return isset($compare[$token[0]]);
        } else {
            return isset($compare[$token]);
        }
    }

    private static function tokenIsIgnored($token)
    {
        return self::tokenIs($token, self::$ignore);
    }

    private static function tokenIsOperator($token)
    {
        if (KINT_PHP56) {
            self::$operator[T_POW] = true;
            self::$operator[T_POW_EQUAL] = true;
        }

        if (KINT_PHP70) {
            self::$operator[T_SPACESHIP] = true;
        }

        return self::tokenIs($token, self::$operator);
    }

    private static function tokenIsStrip($token)
    {
        if (KINT_PHP53) {
            self::$strip[T_NS_SEPARATOR] = true;
        }

        return self::tokenIs($token, self::$strip);
    }

    private static function tokensToString(array $tokens)
    {
        $out = '';

        foreach ($tokens as $token) {
            if (is_string($token)) {
                $out .= $token;
            } elseif (is_array($token)) {
                $out .= $token[1];
            }
        }

        return $out;
    }

    private static function tokensTrim(array $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (self::tokenIsIgnored($token)) {
                unset($tokens[$index]);
            } else {
                break;
            }
        }

        $tokens = array_reverse($tokens);

        foreach ($tokens as $index => $token) {
            if (self::tokenIsIgnored($token)) {
                unset($tokens[$index]);
            } else {
                break;
            }
        }

        return array_reverse($tokens);
    }

    private static function tokensFormatted(array $tokens)
    {
        $space = false;

        $tokens = self::tokensTrim($tokens);

        $output = array();

        foreach ($tokens as $index => $token) {
            if (self::tokenIsIgnored($token)) {
                if ($space) {
                    continue;
                }

                $last = self::lastRealToken($tokens, $index);
                $next = self::nextRealToken($tokens, $index);

                if (self::tokenIsStrip($last) && !self::tokenIsOperator($next)) {
                    continue;
                } elseif (self::tokenIsStrip($next) && !self::tokenIsOperator($last)) {
                    continue;
                }

                $token = ' ';
                $space = true;
            } else {
                $space = false;
            }

            $output[] = $token;
        }

        return $output;
    }
}
