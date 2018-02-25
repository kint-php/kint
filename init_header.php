<?php

if (defined('KINT_DIR')) {
    return;
}

if (version_compare(PHP_VERSION, '5.3') < 0) {
    throw new Exception('Kint 3.0 requires PHP 5.3 or higher');
}

define('KINT_DIR', __DIR__);
define('KINT_WIN', DIRECTORY_SEPARATOR !== '/');
define('KINT_PHP56', (version_compare(PHP_VERSION, '5.6') >= 0));
define('KINT_PHP70', (version_compare(PHP_VERSION, '7.0') >= 0));
define('KINT_PHP72', (version_compare(PHP_VERSION, '7.2') >= 0));
