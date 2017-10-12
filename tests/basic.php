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
$skipnotice = 0;

/**
 * Exits as a failure for any error.
 */
function error($errno)
{
    global $error, $skipnotice;

    if ($skipnotice && $errno === E_NOTICE) {
        --$skipnotice;

        return true;
    } else {
        $skipnotice = 0;
        $error = true;

        return false;
    }
}

set_error_handler('error');

require dirname(__FILE__).'/../'.getenv('KINT_FILE');

// Double require to test include guard, and skip the notice that always happens
// in double included phars because of the constant set by __HALT_COMPILER()
$skipnotice = 1;
require dirname(__FILE__).'/../'.getenv('KINT_FILE'); // Double to test include guard

$skipnotice = 0;

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

Kint::$return = true;

echo 'CLI'.PHP_EOL;
preg_match($expected, d($testdata)) || exit(1);

Kint::$cli_detection = false;

echo 'RICH'.PHP_EOL;
$out = array(0 => Kint::dump($testdata));
preg_match($expected, $out[0]) || exit(1);
echo 'PLAIN'.PHP_EOL;
preg_match($expected, s($testdata)) || exit(1);
echo 'TEXT'.PHP_EOL;
preg_match($expected, ~~d($testdata)) || exit(1);

if ($error) {
    echo 'Errors occurred'.PHP_EOL;
    exit(1);
} else {
    exit(0);
}
