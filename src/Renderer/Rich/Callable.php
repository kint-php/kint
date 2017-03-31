<?php

class Kint_Renderer_Rich_Callable extends Kint_Renderer_Rich_Plugin
{
    protected static $cache = array(
        'method' => array(),
        'closure' => array(),
    );

    public function render($o)
    {
        if ($o instanceof Kint_Object_Method) {
            if (!isset(self::$cache['method'][$o->owner_class])) {
                self::$cache['method'][$o->owner_class] = array();
            }
            if (!isset(self::$cache['method'][$o->owner_class][$o->name])) {
                self::$cache['method'][$o->owner_class][$o->name] = array();
            }
            $cache = &self::$cache['method'][$o->owner_class][$o->name];
        } elseif ($o instanceof Kint_Object_Closure) {
            if (!isset(self::$cache['closure'][$o->hash])) {
                self::$cache['closure'][$o->hash] = array();
            }
            $cache = &self::$cache['closure'][$o->hash];
        }

        if ($o instanceof Kint_Object_Method) {
            if (empty($cache)) {
                $cache['children'] = $this->renderer->renderChildren($o);
            }

            $children = $cache['children'];
        } else {
            $children = $this->renderer->renderChildren($o);
        }

        if (empty($cache['header'])) {
            $header = '';

            if (($s = $o->getModifiers()) !== null) {
                $header .= '<var>'.$s.'</var> ';
            }

            if (($s = $o->getName()) !== null) {
                $header .= '<dfn>'.Kint_Object_Blob::escape($s).'('.Kint_Object_Blob::escape($o->getParams()).')</dfn>';
            }

            if (!empty($o->returntype)) {
                $header .= ': <var>'.Kint_Object_Blob::escape($o->returntype).'</var>';
            }

            if (($s = $o->getValueShort()) !== null) {
                if (Kint_Renderer_Rich::$strlen_max && Kint_Object_Blob::strlen($s) > Kint_Renderer_Rich::$strlen_max) {
                    $s = substr($s, 0, Kint_Renderer_Rich::$strlen_max).'...';
                }
                $header .= ' '.Kint_Object_Blob::escape($s);
            }

            $cache['header'] = $header;
        }

        $header = $this->renderer->renderHeaderWrapper($o, (bool) strlen($children), $cache['header']);

        return '<dl>'.$header.$children.'</dl>';
    }
}
