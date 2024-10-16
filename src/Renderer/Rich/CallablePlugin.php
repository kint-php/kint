<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jonathan Vollebregt (jnvsor@gmail.com), Rokas Šleinius (raveren@gmail.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Kint\Renderer\Rich;

use Kint\Renderer\RichRenderer;
use Kint\Utils;
use Kint\Zval\ClosureValue;
use Kint\Zval\Context\MethodContext;
use Kint\Zval\MethodValue;
use Kint\Zval\Value;

class CallablePlugin extends ClosurePlugin
{
    protected static array $method_cache = [];

    public function renderValue(Value $o): ?string
    {
        if ($o instanceof MethodValue) {
            return $this->renderMethod($o);
        }

        if ($o instanceof ClosureValue) {
            return parent::renderValue($o);
        }

        return null;
    }

    protected function renderMethod(MethodValue $o): ?string
    {
        $c = $o->getContext();

        if (!$c instanceof MethodContext) {
            return null;
        }

        if (!isset(self::$method_cache[$c->owner_class][$c->name])) {
            $children = $this->renderer->renderChildren($o);

            $header = '<var>'.$c->getModifiers();

            if ($o->callable_bag->return_reference) {
                $header .= ' '.$this->renderer->escape('&');
            }

            $header .= '</var> ';

            $function = $this->renderer->escape($o->getDisplayName());

            if (null !== ($url = $o->getPhpDocUrl())) {
                $function = '<a href="'.$url.'" target=_blank>'.$function.'</a>';
            }

            $header .= '<dfn>'.$function.'</dfn>';

            if (null !== $o->callable_bag->returntype) {
                $header .= ': <var>';
                $header .= $this->renderer->escape($o->callable_bag->returntype).'</var>';
            } elseif (null !== $o->callable_bag->docstring) {
                if (\preg_match('/@return\\s+(.*)\\r?\\n/m', $o->callable_bag->docstring, $matches)) {
                    if (\trim($matches[1])) {
                        $header .= ': <var>'.$this->renderer->escape(\trim($matches[1])).'</var>';
                    }
                }
            }

            if (null !== ($s = $o->getValueShort())) {
                if (RichRenderer::$strlen_max) {
                    $s = Utils::truncateString($s, RichRenderer::$strlen_max);
                }
                $header .= ' '.$this->renderer->escape($s);
            }

            self::$method_cache[$c->owner_class][$c->name] = [
                'header' => $header,
                'children' => $children,
            ];
        }

        $children = self::$method_cache[$c->owner_class][$c->name]['children'];

        $header = $this->renderer->renderHeaderWrapper(
            $c,
            (bool) \strlen($children),
            self::$method_cache[$c->owner_class][$c->name]['header']
        );

        return '<dl>'.$header.$children.'</dl>';
    }
}
