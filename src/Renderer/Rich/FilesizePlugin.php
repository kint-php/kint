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

use Kint\Utils;
use Kint\Zval\Value;

class FilesizePlugin extends AbstractPlugin implements ValuePluginInterface
{
    public function renderValue(Value $o): ?string
    {
        if (null === $o->size) {
            return null;
        }

        $header = $this->renderer->renderHeader($o);
        $size = Utils::getHumanReadableBytes($o->size);

        $num = null;
        $header = \str_replace('('.$o->size.')', '('.$size['value'].$size['unit'].')', $header, $num);
        if (1 !== $num) {
            return null;
        }

        $children = $this->renderer->renderChildren($o);
        $header = $this->renderer->renderHeaderWrapper($o->getContext(), (bool) \strlen($children), $header);

        return '<dl>'.$header.$children.'</dl>';
    }
}
