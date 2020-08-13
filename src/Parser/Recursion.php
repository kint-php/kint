<?php

namespace Kint\Parser;

use function is_array;
use function is_object;
use function spl_object_hash;
use function uniqid;

/**
 * Recursion tracker
 *
 * Collects references to arrays and objects introduced during the
 * inspection of a variable inside the {@link Kint\Parser\Parser}.
 * Any variables introduced more than once constitutes recursion.
 *
 * @author Kaloyan KT Tsvetkov <kaloyan@gmail.com>
 */
final class Recursion
{
    /**
     * @var array list of referenced arrays
     * @see Recursion::isArrayRecursion();
     */
    protected $knownArrays = [];

    /**
     * @var string marker for detecting array references vs copies
     * @see Recursion::isArrayRecursion();
     */
    protected $marker;

    /**
     * Creates new {@link Kint\Parser\Recursion} object
     */
    function __construct()
    {
        $this->marker = uniqid("kint\0", true);
    }

    /**
     * Checks for array recursion
     *
     * @param array &$var
     * @return bool
     */
    function isArrayRecursion(array &$var) : bool
    {
        foreach ($this->knownArrays as &$known)
        {
            if ($known === $var)
            {
                // test if they are copies of one another,
                // or they are reference to the same array
                $var[ $this->marker ] = 1;
                $recursion = !empty($known[ $this->marker ]);
                unset($var[ $this->marker ]);
                return $recursion;
            }
        }

        // we are only interested in arrays with
        // nested other arrays and objects inside,
        // where the recursion can occur
        foreach ($var as $k => &$v)
        {
            if (is_object($v) || is_array($v))
            {
                $this->knownArrays[] =& $var;
                break;
            }
        }

        return false;
    }

    /**
     * Cleans the collected array references
     */
    function cleanArrays()
    {
        $this->knownArrays = [];
    }

    /**
     * @var array list of referenced objects
     * @see Recursion::isObjectRecursion();
     */
    protected $knownObjects = [];

    /**
     * Checks for object recursion
     *
     * @param object &$var
     * @return bool
     */
    function isObjectRecursion(&$var) : bool
    {
        // can not type-hint generic object values,
        // so the best option is to use is_object()
        if (!is_object($var))
        {
            return false;
        }

        // we can foreach the list of $knownObjects
        // as we do with the arrays, but using the
        // spl_object_hash() is faster
        $hash = spl_object_hash($var);
        if (!empty($this->knownObjects[ $hash ]))
        {
            return true;
        }

        $this->knownObjects[ $hash ] =& $var;
        return false;
    }

    /**
     * Cleans the collected object references
     */
    function cleanObjects()
    {
        $this->knownObjects = [];
    }
}
