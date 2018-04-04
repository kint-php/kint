<?php

namespace Kint;

/**
 * A collection of utility methods. Should all be static methods with no dependencies.
 */
class Utils
{
    /**
     * Turns a byte value into a human-readable representation.
     *
     * @param int $value Amount of bytes
     *
     * @return array Human readable value and unit
     */
    public static function getHumanReadableBytes($value)
    {
        static $unit = array('B', 'KB', 'MB', 'GB', 'TB');

        $i = floor(log($value, 1024));
        $i = min($i, 4); // Only go up to TB

        return array(
            'value' => (float) ($value / pow(1024, $i)),
            'unit' => $unit[$i],
        );
    }

    public static function isSequential(array $array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    public static function composerGetExtras($key = 'kint')
    {
        $extras = array();

        if (strpos(KINT_DIR, 'phar://') === 0) {
            // Only run inside phar file, so skip for code coverage
            return $extras; // @codeCoverageIgnore
        }

        $folder = KINT_DIR.'/vendor';

        for ($i = 0; $i < 4; ++$i) {
            $installed = $folder.'/composer/installed.json';

            if (file_exists($installed) && is_readable($installed)) {
                $packages = json_decode(file_get_contents($installed), true);

                foreach ($packages as $package) {
                    if (isset($package['extra'][$key]) && is_array($package['extra'][$key])) {
                        $extras = array_replace($extras, $package['extra'][$key]);
                    }
                }

                $folder = dirname($folder);

                if (file_exists($folder.'/composer.json') && is_readable($folder.'/composer.json')) {
                    $composer = json_decode(file_get_contents($folder.'/composer.json'), true);

                    if (isset($composer['extra'][$key]) && is_array($composer['extra'][$key])) {
                        $extras = array_replace($extras, $composer['extra'][$key]);
                    }
                }

                break;
            } else {
                $folder = dirname($folder);
            }
        }

        return $extras;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function composerSkipFlags()
    {
        $extras = self::composerGetExtras();

        if (!empty($extras['disable-facade']) && !defined('KINT_SKIP_FACADE')) {
            define('KINT_SKIP_FACADE', true);
        }

        if (!empty($extras['disable-helpers']) && !defined('KINT_SKIP_HELPERS')) {
            define('KINT_SKIP_HELPERS', true);
        }
    }

    public static function isTrace(array $trace)
    {
        if (!self::isSequential($trace)) {
            return false;
        }

        static $bt_structure = array(
            'function' => 'string',
            'line' => 'integer',
            'file' => 'string',
            'class' => 'string',
            'object' => 'object',
            'type' => 'string',
            'args' => 'array',
        );

        $file_found = false;

        foreach ($trace as $frame) {
            if (!is_array($frame) || !isset($frame['function'])) {
                return false;
            }

            foreach ($frame as $key => $val) {
                if (!isset($bt_structure[$key])) {
                    return false;
                } elseif (gettype($val) !== $bt_structure[$key]) {
                    return false;
                } elseif ($key === 'file') {
                    $file_found = true;
                }
            }
        }

        return $file_found;
    }

    public static function traceFrameIsListed(array $frame, array $matches)
    {
        if (isset($frame['class'])) {
            $called = array(strtolower($frame['class']), strtolower($frame['function']));
        } else {
            $called = strtolower($frame['function']);
        }

        return in_array($called, $matches, true);
    }

    public static function normalizeAliases(array &$aliases)
    {
        foreach ($aliases as $index => &$alias) {
            if (is_array($alias) && count($alias) === 2) {
                $alias = array_values(array_filter($alias, 'is_string'));

                if (count($alias) === 2 &&
                    preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $alias[1]) &&
                    preg_match('/^[a-zA-Z_\x7f-\xff\\\\][a-zA-Z0-9_\x7f-\xff\\\\]*$/', $alias[0])
                ) {
                    $alias = array(
                        strtolower(ltrim($alias[0], '\\')),
                        strtolower($alias[1]),
                    );
                } else {
                    unset($aliases[$index]);
                    continue;
                }
            } elseif (is_string($alias)) {
                if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $alias)) {
                    $alias = strtolower($alias);
                } else {
                    unset($aliases[$index]);
                    continue;
                }
            } else {
                unset($aliases[$index]);
            }
        }

        $aliases = array_values($aliases);
    }
}
