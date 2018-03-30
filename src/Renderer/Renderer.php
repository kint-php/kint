<?php

namespace Kint\Renderer;

use Kint\Object\BasicObject;
use Kint\Object\InstanceObject;

abstract class Renderer
{
    const SORT_NONE = 0;
    const SORT_VISIBILITY = 1;
    const SORT_FULL = 2;

    protected $parameters;

    abstract public function render(BasicObject $o);

    /**
     * It's a constructor. It constructs.
     *
     * @param array $parameters Array with initial kint state information
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns the first compatible plugin available.
     *
     * @param array $plugins Array of hints to class strings
     * @param array $hints   Array of object hints
     *
     * @return array Array of hints to class strings filtered and sorted by object hints
     */
    public function matchPlugins(array $plugins, array $hints)
    {
        $out = array();

        foreach ($hints as $key) {
            if (isset($plugins[$key])) {
                $out[$key] = $plugins[$key];
            }
        }

        return $out;
    }

    public function parserPlugins(array $plugins)
    {
        return $plugins;
    }

    public function preRender()
    {
        return '';
    }

    public function postRender()
    {
        return '';
    }

    public static function sortPropertiesFull(BasicObject $a, BasicObject $b)
    {
        $sort = BasicObject::sortByAccess($a, $b);
        if ($sort) {
            return $sort;
        }

        $sort = BasicObject::sortByName($a, $b);
        if ($sort) {
            return $sort;
        }

        return InstanceObject::sortByHierarchy($a->owner_class, $b->owner_class);
    }

    /**
     * Sorts an array of BasicObject.
     *
     * @param BasicObject[] $contents Object properties to sort
     * @param int           $sort
     *
     * @return BasicObject[]
     */
    public static function sortProperties(array $contents, $sort)
    {
        switch ($sort) {
            case self::SORT_VISIBILITY:
                $containers = array(
                    BasicObject::ACCESS_PUBLIC => array(),
                    BasicObject::ACCESS_PROTECTED => array(),
                    BasicObject::ACCESS_PRIVATE => array(),
                    BasicObject::ACCESS_NONE => array(),
                );

                foreach ($contents as $item) {
                    $containers[$item->access][] = $item;
                }

                return call_user_func_array('array_merge', $containers);
            case self::SORT_FULL:
                usort($contents, array('Kint\\Renderer\\Renderer', 'sortPropertiesFull'));
                // fall through
            default:
                return $contents;
        }
    }
}
