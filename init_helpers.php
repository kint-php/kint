<?php

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

use Kint\Kint;
use Kint\Renderer\CliRenderer;

if (!\function_exists('d')) {
    /**
     * Alias of Kint::dump().
     *
     * @return int|string
     */
    function d()
    {
        $args = \func_get_args();

        return \call_user_func_array(['Kint', 'dump'], $args);
    }

    Kint::$aliases[] = 'd';
}

if (!function_exists('kd')) {
    /**
     * Alias of Kint::dump(); die; is compatible with symfony & laravel (it leaves their dd helper alone)
     */
    function kd()
    {
        $args = func_get_args();

        call_user_func_array(['Kint', 'dump'], $args);

        die;
    }

    Kint::$aliases[] = 'kd';
}

if (!\function_exists('s')) {
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
     * @return int|string
     */
    function s()
    {
        if (false === Kint::$enabled_mode) {
            return 0;
        }

        $kstash = Kint::$enabled_mode;
        $cstash = CliRenderer::$cli_colors;

        if (Kint::MODE_TEXT !== Kint::$enabled_mode) {
            Kint::$enabled_mode = Kint::MODE_PLAIN;

            if (PHP_SAPI === 'cli' && true === Kint::$cli_detection) {
                Kint::$enabled_mode = Kint::$mode_default_cli;
            }
        }

        CliRenderer::$cli_colors = false;

        $args = \func_get_args();
        $out = \call_user_func_array(['Kint', 'dump'], $args);

        Kint::$enabled_mode = $kstash;
        CliRenderer::$cli_colors = $cstash;

        return $out;
    }

    Kint::$aliases[] = 's';
}

if (!function_exists('sd')) {
    function sd()
    {
        $args = func_get_args();

        call_user_func_array('ss', $args);

        die;
    }

    Kint::$aliases[] = 'sd';
}

if (! function_exists('ss')) {
    /**
     * Plain text output for Kint::dump()
     */
    function ss()
    {
        if (! Kint::$enabled_mode) {
            return 0;
        }

        $stash = Kint::$enabled_mode;
        Kint::$enabled_mode = Kint::MODE_TEXT;

        $args = func_get_args();
        $out = call_user_func_array(['Kint', 'dump'], $args);

        Kint::$enabled_mode = $stash;

        return $out;
    }

    Kint::$aliases[] = 'ss';
}
