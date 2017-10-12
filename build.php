<?php

use Seld\PharUtils\Timestamps;
use Symfony\Component\Finder\Finder;

require_once __DIR__.'/vendor/autoload.php';

mkdir(__DIR__.'/build');

$outpath = __DIR__.'/build/kint.phar';

unlink($outpath);
$phar = new Phar($outpath);
$phar->setStub('<?php require \'phar://\'.__FILE__.\'/init_phar.php\'; __HALT_COMPILER();');

$pathlen = strlen(__DIR__);

$finder = new Finder();
foreach ($finder->files()->name('*.php')->in(__DIR__.'/src')->sortByName() as $file) {
    $local = substr($file, $pathlen);
    $phar->addFile($file, $local);
}

$finder = new Finder();
foreach ($finder->files()->in(__DIR__.'/resources/compiled')->sortByName() as $file) {
    $local = substr($file, $pathlen);
    $phar->addFile($file, $local);
}

$phar->addFile(__DIR__.'/init_phar.php', '/init_phar.php');
$phar->addFile(__DIR__.'/init_header.php', '/init_header.php');
$phar->addFile(__DIR__.'/init_footer.php', '/init_footer.php');
$phar->addFile(__DIR__.'/init_helpers.php', '/init_helpers.php');

$phar = new Timestamps($outpath);
$phar->updateTimestamps();
$phar->save($outpath, Phar::SHA512);
