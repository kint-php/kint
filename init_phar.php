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

require_once __DIR__.'/init.php';

// Force helpers initialization, since we're in a phar
require_once __DIR__.'/init_helpers.php';
