<?php

abstract class Kint_Renderer
{
    protected $parameters;

    abstract public function render(Kint_Object $o);

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
}
