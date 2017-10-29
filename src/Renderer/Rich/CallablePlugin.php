<?php

namespace Kint\Renderer\Rich;

use Kint\Object\BasicObject;
use Kint\Object\BlobObject;
use Kint\Object\MethodObject;
use Kint\Renderer\RichRenderer;

class CallablePlugin extends Plugin implements ObjectPluginInterface
{
    protected static $method_cache = array();

    public function renderObject(BasicObject $o)
    {
        if ($o instanceof MethodObject && strlen($o->owner_class) && strlen($o->name) && !empty(self::$method_cache[$o->owner_class][$o->name])) {
            $children = self::$method_cache[$o->owner_class][$o->name]['children'];

            $header = $this->renderer->renderHeaderWrapper(
                $o,
                (bool) strlen($children),
                self::$method_cache[$o->owner_class][$o->name]['header']
            );

            return '<dl>'.$header.$children.'</dl>';
        }

        $children = $this->renderer->renderChildren($o);

        $header = '';

        if (($s = $o->getModifiers()) !== null) {
            $header .= '<var>'.$s.'</var> ';
        }

        if (($s = $o->getName()) !== null) {
            $function = $this->renderer->escape($s).'('.$this->renderer->escape($o->getParams()).')';

            if (($url = $o->getPhpDocUrl()) !== null) {
                $function = '<a href="'.$url.'" target=_blank>'.$function.'</a>';
            }

            $header .= '<dfn>'.$function.'</dfn>';
        }

        if (!empty($o->returntype)) {
            $header .= ': <var>'.$this->renderer->escape($o->returntype).'</var>';
        }

        if (($s = $o->getValueShort()) !== null) {
            if (RichRenderer::$strlen_max && BlobObject::strlen($s) > RichRenderer::$strlen_max) {
                $s = substr($s, 0, RichRenderer::$strlen_max).'...';
            }
            $header .= ' '.$this->renderer->escape($s);
        }

        if ($o instanceof MethodObject && strlen($o->owner_class) && strlen($o->name)) {
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
