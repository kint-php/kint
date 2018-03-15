<?php

if (defined('KINT_DIR')) {
    return;
}

spl_autoload_register(function ($class) {
    $class = explode('\\', $class);

    if (array_shift($class) !== 'Kint') {
        return;
    }

    $file = __DIR__.'/src/'.implode('/', $class).'.php';

    if (file_exists($file)) {
        require_once __DIR__.'/src/'.implode('/', $class).'.php';
    }
});

if (!defined('KINT_SKIP_FACADE')) {
    define('KINT_SKIP_FACADE', false);
}

if (!defined('KINT_SKIP_HELPERS')) {
    define('KINT_SKIP_HELPERS', false);
}

require_once __DIR__.'/init.php';
