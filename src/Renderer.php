<?php

namespace Kint;

abstract class Renderer
{
    public $plugins = array();

    abstract public function render(Object $o);

    public function __construct(array $names, $modifiers, array $callee, array $mini_trace, array $previous_caller)
    {
    }

    public function plugins_render(Object\Representation $rep)
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
