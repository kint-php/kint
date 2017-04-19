<?php
/**
 * Kint is a zero-setup debugging tool to output information about variables and stack traces prettily and comfortably.
 *
 * https://github.com/kint-php/kint
 */
if (defined('KINT_DIR')) {
    return;
}

require_once dirname(__FILE__).'/init_header.php';

// Check composer for extras disabling default helper functions
if (!Kint::composerGetDisableHelperFunctions()) {
    require_once KINT_DIR.'/init_helpers.php';
}

// Dynamic default settings
require_once KINT_DIR.'/init_footer.php';
