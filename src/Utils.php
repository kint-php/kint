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
}
