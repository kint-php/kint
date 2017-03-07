<?php
/**
 * A basic sanity test to ensure Kint doesn't explode
 * on PHP versions below composer/phpunit support.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

$error = false;

/**
 * Exits as a failure for any error.
 */
function error()
{
    global $error;
    $error = true;

    return false;
}

set_error_handler('error');

include dirname(__FILE__).'/../init.php';

$testdata = array(
    1234,
    (object) array('abc' => 'def'),
    1234.5678,
    'Good news everyone! I\'ve got some bad news!',
    null,
);

$testdata[] = &$testdata;

d($testdata);

Kint::$cli_detection = false;

d($testdata);
s($testdata);
~d($testdata);

if ($error) {
    exit(1);
} else {
    exit(0);
}
