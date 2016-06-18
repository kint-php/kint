<?php

abstract class Kint_Renderer
{
    public $plugins = array();

    abstract public function render(Kint_Object $o);

    public function __construct(array $names, $modifiers, array $callee, array $mini_trace, array $previous_caller)
    {
    }

    public function plugins_render(Kint_Object_Representation $rep)
    {
        if (array_intersect(array_keys($this->plugins), $rep->renderers)) {
            foreach ($rep->renderers as $plugin) {
                if (isset($this->plugins[$plugin])) {
                    $p = new $this->plugins[$plugin]($this);

                    return $p->render($rep);
                }
            }
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
