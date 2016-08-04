<?php

abstract class Kint_Renderer
{
    public $plugin_map = array();

    abstract public function render(Kint_Object $o);

    public function __construct(array $names = null, array $parameters = null, $modifiers = null, array $callee = null, array $caller = null, array $mini_trace = array())
    {
    }

    public function plugins_render(Kint_Object_Representation $rep)
    {
        if ($plugins = array_intersect(array_keys($this->plugin_map), $rep->renderers)) {
            $p = new $this->plugin_map[reset($plugins)]($this);

            return $p->render($rep);
        }
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
