<?php

namespace Kint\Parser;

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
                return true;
            }
        }

        $this->knownArrays[] =& $var;
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
        $hash = \spl_object_hash($var);
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
