<?php
/**
 * Kint is a zero-setup debugging tool to output information about variables and stack traces prettily and comfortably.
 *
 * https://github.com/kint-php/kint
 */
if (defined('KINT_DIR')) {
    return;
}

require_once __DIR__.'/init_header.php';
require_once __DIR__.'/Kint.php';

// Check composer for extras disabling default helper functions
if (!Kint::composerGetDisableHelperFunctions()) {
    require_once __DIR__.'/init_helpers.php';
}

// Dynamic default settings
require_once __DIR__.'/init_footer.php';
