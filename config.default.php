<?php
isset( $GLOBALS['_kint_settings'] ) or $GLOBALS['_kint_settings'] = array();
$_kintSettings = &$GLOBALS['_kint_settings'];


/** @var bool if set to false, kint will become silent, same as Kint::enabled(false) or Kint::$enabled = false */
$_kintSettings['enabled'] = true;


/**
 * @var bool whether to display where kint was called from
 */
$_kintSettings['displayCalledFrom'] = true;


/**
 * @var string format of the link to the source file in trace entries. Use %f for file path, %l for line number.
 * Defaults to xdebug.file_link_format if not set.
 *
 * [!] EXAMPLE (works with for phpStorm and RemoteCall Plugin):
 *
 * $_kintSettings['fileLinkFormat'] = 'http://localhost:8091/?message=%f:%l';
 *
 */
$_kintSettings['fileLinkFormat'] = ini_get( 'xdebug.file_link_format' );


/**
 * @var array base directories of your application that will be displayed instead of the full path. Keys are paths,
 * values are replacement strings
 *
 * Defaults to array( $_SERVER['DOCUMENT_ROOT'] => '&lt;ROOT&gt;' )
 *
 * [!] EXAMPLE (for Kohana framework):
 *
 * $_kintSettings['appRootDirs'] = array(
 *      APPPATH => 'APPPATH', // make sure the constants are already defined at the time of including this config file
 *      SYSPATH => 'SYSPATH',
 *      MODPATH => 'MODPATH',
 *      DOCROOT => 'DOCROOT',
 * );
 *
 * [!] EXAMPLE #2 (for a semi-universal approach)
 *
 * $_kintSettings['appRootDirs'] = array(
 *      realpath( __DIR__ . '/../../..' ) => 'ROOT', // go up as many levels as needed in the realpath() param
 * );
 *
 * $_kintSettings['fileLinkFormat'] = 'http://localhost:8091/?message=%f:%l';
 *
 */
$_kintSettings['appRootDirs'] = array(
	$_SERVER['DOCUMENT_ROOT'] => '&lt;ROOT&gt;'
);


/**
 * @var callable|null
 *
 * @param array $step each step of the backtrace is passed to this callback to clean it up or skip it entirely
 *
 * @return array|null you can return null if you want to bypass outputting this step
 *
 * [!] EXAMPLE:
 *
 * $_kintSettings['traceCleanupCallback'] = function( $traceStep ) {
 *      if ( isset( $traceStep['class'] ) && strtolower( $traceStep['class'] ) === 'errorHandler' ) {
 *           return null;
 *      }
 *
 *      if ( isset( $traceStep['function'] ) && strtolower( $traceStep['function'] ) === '__tostring' ) {
 *          $traceStep['function'] = "[object converted to string]";
 *      }
 *
 *       return $traceStep;
 * };
 */
$_kintSettings['traceCleanupCallback'] = null;


/** @var int max length of string before it is truncated and displayed separately in full. Zero or false to disable */
$_kintSettings['maxStrLength'] = 60;

/** @var array possible alternative char encodings in order of probability, eg. array('windows-1251') */
$_kintSettings['charEncodings'] = array();


/** @var int max array/object levels to go deep, if zero no limits are applied */
$_kintSettings['maxLevels'] = 5;


/** @var bool whether dumped indexed arrays that are in ideal sequence are displayed */
$_kintSettings['hideSequentialKeys'] = true;


/** @var string name of theme for rich view */
$_kintSettings['theme'] = 'original';


/**
 * @var callback filters array/object keys before outputting; return false if you do not wish to see it in the output
 *
 * @param string $key the key being output
 * @param mixed  $val the contents of the dumped element in case you need it
 *
 * @return bool return false to skip displaying
 *
 * [!] EXAMPLE:
 *
 * $_kintSettings['keyFilterCallback'] = function( $key, $val ) {
 *     if ( preg_match( '#_mt$#', $key ) ) {
 *         return false;
 *     }
 *
 *     if ( $val === '--testing--' ) {
 *         return false;
 *     }
 *
 *     // no need to return true to continue output
 * };
 *
 */
$_kintSettings['keyFilterCallback'] = null;


/** @var bool only set to true if you want to develop kint and know what you're doing */
$_kintSettings['devel'] = false;


unset( $_kintSettings );