<?php
/**
 * A basic sanity test for Kint.
 *
 * This bears the dual responsibility of ensuring Kint (at least
 * superficially) works on PHP versions too low to support PHPunit/composer,
 * and ensuring that Kint works when init is included manually, as opposed
 * to autoloaded through composer.
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

$expected = '0.+?integer.+?1234.+?1.+?stdClass.+?1.+?public.+?abc.+?string.+?3.+?def.+?2.+?double.+?1234\\.5678.+?3.+?string.+?43.+?Good news everyone! I\'ve got some bad news!.+?4.+?null.+?5.+?(&|&amp;)array';
$expected = '/'.$expected.'.+?6.+?'.$expected.'.+RECURSION/si';

echo 'CLI'.PHP_EOL;
preg_match($expected, @d($testdata)) || exit(1);

Kint::$return = true;
Kint::$cli_detection = false;

echo 'RICH'.PHP_EOL;
preg_match($expected, d($testdata)) || exit(1);
echo 'PLAIN'.PHP_EOL;
preg_match($expected, s($testdata)) || exit(1);

Kint::$enabled_mode = Kint::MODE_TEXT;
echo 'TEXT'.PHP_EOL;
preg_match($expected, d($testdata)) || exit(1);

if ($error) {
    echo 'Errors occurred'.PHP_EOL;
    exit(1);
} else {
    exit(0);
}
