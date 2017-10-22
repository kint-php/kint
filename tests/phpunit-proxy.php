<?php
/**
 * This file loads a built file before phpunit, since using phpunit directly
 * will load composer and automatically start using the loose files.
 */
require __DIR__.'/../'.getenv('KINT_FILE');

$composer = require __DIR__.'/../vendor/autoload.php';

// Register the composer autoloader after the KINT_FILE autoloader
$composer->unregister();
$composer->register();

require __DIR__.'/../vendor/bin/phpunit';
