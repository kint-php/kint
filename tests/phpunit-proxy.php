<?php
/**
 * This file loads a built file before phpunit, since using phpunit directly
 * will load composer and automatically start using the loose files.
 */
require __DIR__.'/../'.getenv('KINT_FILE');
require __DIR__.'/../vendor/bin/phpunit';
