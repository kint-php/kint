<?php

namespace Kint\Renderer\Rich;

use Kint\Object\BasicObject;
use Kint\Object\BlobObject;
use Kint\Object\ClosureObject;
use Kint\Object\MethodObject;
use Kint\Renderer\RichRenderer;

class CallablePlugin extends Plugin implements ObjectPluginInterface
{
    protected static $method_cache = array();

    public function renderObject(BasicObject $o)
    {
        if ($o instanceof MethodObject) {
            return $this->renderMethod($o);
        } elseif ($o instanceof ClosureObject) {
            return $this->renderClosure($o);
        } else {
            return $this->renderCallable($o);
        }
    }

    protected function renderClosure(ClosureObject $o)
    {
        $children = $this->renderer->renderChildren($o);

        $header = '';

        if (($s = $o->getModifiers()) !== null) {
            $header .= '<var>'.$s.'</var> ';
        }

        if (($s = $o->getName()) !== null) {
            $header .= '<dfn>'.$this->renderer->escape($s).'('.$this->renderer->escape($o->getParams()).')</dfn>';
        }

        if (($s = $o->getValueShort()) !== null) {
            if (RichRenderer::$strlen_max && BlobObject::strlen($s) > RichRenderer::$strlen_max) {
                $s = substr($s, 0, RichRenderer::$strlen_max).'...';
            }
            $header .= ' '.$this->renderer->escape($s);
        }

        return '<dl>'.$this->renderer->renderHeaderWrapper($o, (bool) strlen($children), $header).$children.'</dl>';
    }

    protected function renderCallable(BasicObject $o)
    {
        $children = $this->renderer->renderChildren($o);

        $header = '';

        if (($s = $o->getModifiers()) !== null) {
            $header .= '<var>'.$s.'</var> ';
        }

        if (($s = $o->getName()) !== null) {
            $header .= '<dfn>'.$this->renderer->escape($s).'</dfn>';
        }

        if (($s = $o->getValueShort()) !== null) {
            if (RichRenderer::$strlen_max && BlobObject::strlen($s) > RichRenderer::$strlen_max) {
                $s = substr($s, 0, RichRenderer::$strlen_max).'...';
            }
            $header .= ' '.$this->renderer->escape($s);
        }

        return '<dl>'.$this->renderer->renderHeaderWrapper($o, (bool) strlen($children), $header).$children.'</dl>';
    }

    protected function renderMethod(MethodObject $o)
    {
        if (!empty(self::$method_cache[$o->owner_class][$o->name])) {
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

        if (($s = $o->getModifiers()) !== null || $o->return_reference) {
            $header .= '<var>'.$s;

            if ($o->return_reference) {
                if ($s) {
                    $header .= ' ';
                }
                $header .= $this->renderer->escape('&');
            }

            $header .= '</var> ';
        }

        if (($s = $o->getName()) !== null) {
            $function = $this->renderer->escape($s).'('.$this->renderer->escape($o->getParams()).')';

            if (($url = $o->getPhpDocUrl()) !== null) {
                $function = '<a href="'.$url.'" target=_blank>'.$function.'</a>';
            }

            $header .= '<dfn>'.$function.'</dfn>';
        }

        if (!empty($o->returntype)) {
            $header .= ': <var>';

            if ($o->return_reference) {
                $header .= $this->renderer->escape('&');
            }

            $header .= $this->renderer->escape($o->returntype).'</var>';
        } elseif ($o->docstring) {
            if (preg_match('/@return\s+(.*)\r?\n/m', $o->docstring, $matches)) {
                if (trim($matches[1])) {
                    $header .= ': <var>'.$this->renderer->escape(trim($matches[1])).'</var>';
                }
            }
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
