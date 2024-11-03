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

namespace Kint\Test\Value;

use Kint\Renderer\AbstractRenderer;
use Kint\Test\KintTestCase;

/**
 * @coversNothing
 */
class AbstractRendererTest extends KintTestCase
{
    /**
     * @covers \Kint\Renderer\AbstractRenderer::getFileLink
     */
    public function testGetFileLink()
    {
        if (\strlen((string) \ini_get('xdebug.file_link_format'))) {
            $this->assertSame(\ini_get('xdebug.file_link_format'), AbstractRenderer::$file_link_format);
        } else {
            $this->assertNull(AbstractRenderer::$file_link_format);
        }

        $file = \bin2hex(\random_bytes(16));
        $line = \random_int(1, PHP_INT_MAX);

        $this->assertNull(AbstractRenderer::getFileLink($file, $line));

        AbstractRenderer::$file_link_format = '<a href="%f:%l">Click here</a>';

        $this->assertSame('<a href="'.$file.':'.$line.'">Click here</a>', AbstractRenderer::getFileLink($file, $line));
    }
}
