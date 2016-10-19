<?php

abstract class Kint_Renderer
{
    abstract public function render(Kint_Object $o);

    public function __construct(array $names = null, array $parameters = null, $modifiers = null, array $callee = null, array $caller = null, array $mini_trace = array())
    {
    }

    /**
     * Returns the first compatible plugin available
     * @param  array  $plugins Array of hints to class strings
     * @param  array  $hints   Array of object hints
     * @return array           Array of hints to class strings filtered and sorted by object hints
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

    public function preRender()
    {
        return '';
    }

    public function postRender()
    {
        return '';
    }
}
