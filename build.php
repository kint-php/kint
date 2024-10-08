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

use Seld\PharUtils\Timestamps;
use Symfony\Component\Finder\Finder;

require_once __DIR__.'/vendor/autoload.php';

if (!\file_exists(__DIR__.'/build')) {
    \mkdir(__DIR__.'/build');
}

$outpath = __DIR__.'/build/kint.phar';

if (\file_exists($outpath)) {
    \unlink($outpath);
}

$phar = new Phar($outpath);
$phar->setStub('<?php
/*
 * '.\str_replace("\n", "\n * ", \trim(\file_get_contents(__DIR__.'/LICENSE'))).'
 */

require \'phar://\'.__FILE__.\'/init_phar.php\'; __HALT_COMPILER();');

$pathlen = \strlen(__DIR__);

$filesToArchive = Finder::create()
    ->files()
    ->in([__DIR__.'/src', __DIR__.'/resources/compiled'])
    ->append([
        __DIR__.'/init_phar.php',
        __DIR__.'/init.php',
        __DIR__.'/init_helpers.php',
    ])
    ->sortByName();

if (KINT_WIN) {
    $filesToArchive->sort(static function (SplFileInfo $a, SplFileInfo $b) {
        $a = \strtr($a->getRealPath() ?: $a->getPathname(), '\\', '/');
        $b = \strtr($b->getRealPath() ?: $b->getPathname(), '\\', '/');

        return \strcmp($a, $b);
    });
}

foreach ($filesToArchive as $file) {
    $local = \substr((string) $file, $pathlen);
    $phar->addFile((string) $file, $local);
    $pi = new PharFileInfo('phar://'.$outpath.$local);
    $pi->chmod(0644);
}

$phar = new Timestamps($outpath);
$phar->updateTimestamps();
$phar->save($outpath, Phar::SHA512);
