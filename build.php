<?php

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

$dir = __DIR__;
$build_dir = $dir.'/build';
$outpath = $build_dir.'/kint.phar';

require_once $dir.'/vendor/autoload.php';

if (ini_get('phar.readonly') == 1) {
    die("Error: Creating phar files is disabled by your system.\nYou must set \"phar.readonly = Off\" in your php.ini to continue.\n");
}

if (!file_exists($build_dir)) {
    \mkdir($build_dir);
}

if (file_exists($outpath)) {
    \unlink($outpath);
}

$phar = new Phar($outpath);
$phar->setStub('<?php
/*
 * '.\str_replace("\n", "\n * ", \trim(\file_get_contents($dir.'/LICENSE'))).'
 */

require \'phar://\'.__FILE__.\'/init_phar.php\'; __HALT_COMPILER();');

$pathlen = \strlen($dir);

foreach (Finder::create()->files()->in(array($dir.'/src', $dir.'/resources/compiled'))->sortByName() as $file) {
    $local = \substr($file, $pathlen);
    $phar->addFile($file, $local);
}

$phar->addFile($dir.'/init_phar.php', '/init_phar.php');
$phar->addFile($dir.'/init.php', '/init.php');
$phar->addFile($dir.'/init_helpers.php', '/init_helpers.php');

$phar = new Timestamps($outpath);
$phar->updateTimestamps();
$phar->save($outpath, Phar::SHA512);

echo "Your kint.phar has been built successfully and is located in the \"build\" directory.\n";
