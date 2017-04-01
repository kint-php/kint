<?php

class Kint_Renderer_Rich_Callable extends Kint_Renderer_Rich_Plugin
{
    protected static $method_cache = array();

    public function render($o)
    {
        if ($o instanceof Kint_Object_Method && strlen($o->owner_class) && strlen($o->name) && !empty(self::$method_cache[$o->owner_class][$o->name])) {
            $children = self::$method_cache[$o->owner_class][$o->name]['children'];

            $header = $this->renderer->renderHeaderWrapper($o, (bool) strlen($children), self::$method_cache[$o->owner_class][$o->name]['header']);

            return '<dl>'.$header.$children.'</dl>';
        }

        $children = $this->renderer->renderChildren($o);

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

        if ($o instanceof Kint_Object_Method && strlen($o->owner_class) && strlen($o->name)) {
            $cache = array(
                'header' => $header,
                'children' => $children,
            );

            if (!isset(self::$method_cache[$o->owner_class])) {
                self::$method_cache[$o->owner_class] = array($o->name => $cache);
            } elseif (!isset(self::$method_cache[$o->owner_class][$o->name])) {
                self::$method_cache[$o->owner_class][$o->name] = $cache;
            }
        }

        $header = $this->renderer->renderHeaderWrapper($o, (bool) strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }
}
