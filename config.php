<?php
isset( $GLOBALS['_kint_settings'] ) or $GLOBALS['_kint_settings'] = array();
$_kintSettings = &$GLOBALS['_kint_settings'];


/** @var bool if set to false, kint will become silent, same as Kint::enabled(false) */
$_kintSettings['enabled'] = true;


/**
 * @var bool whether to display where kint was called from
 */
$_kintSettings['displayCalledFrom'] = true;



/**
 * @var callback filter/skip display of trace entries
 *
 * @param string   $file filename where the function was called
 * @param int      $line [OPTIONAL] the line number in the file (not applicable when used in resource dumps)
 *
 * @return string html - escaped string
 *
 * [!] EXAMPLE:
 *
 * $_kintSettings['pathDisplayCallback'] = function( $file, $line = NULL ) {
 *     $shortenedName = strpos( $file, $_SERVER['DOCUMENT_ROOT'] ) === 0
 *         ? 'DOCUMENT_ROOT' . DIRECTORY_SEPARATOR . substr( $file, strlen( $_SERVER['DOCUMENT_ROOT'] ) )
 *         : $file;
 *
 *     if ( !$line ) { // means this is called from resource type dump
 *         return $shortenedName;
 *     }
 *
 *     return "<u><a class=\"kint-ide-link\" href=\"http://localhost:8091/?message={$file}:{$line}\">"
 *         . $shortenedName
 *         . "</a></u> line <i>{$line}</i>";
 * };
 */
$_kintSettings['pathDisplayCallback'] = "_kintLine";


/**
 * @var callback|null
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


/** @var int max length of string before it is truncated and displayed separately in full */
$_kintSettings['maxStrLength'] = 60;


/** @var bool whether to add a right colored gutter based on the location of the call to the dump */
$_kintSettings['colorCodeLoops'] = true;


/** @var int max array/object levels to go deep, if zero no limits are applied */
$_kintSettings['maxLevels'] = 5;


/** @var bool whether dumped indexed arrays that are in ideal sequence are displayed */
$_kintSettings['hideSequentialKeys'] = true;


/** @var string|null if set, prepends a <head> tag with appropriate meta charset value */
$_kintSettings['charset'] = null;


/**
 * @var callback filters array/object keys before outputting; return false if you do not wish to see it in the output.
 * NOTE, object member keys will be passed here too. The object is first cast to array so read this:
 * http://www.php.net/manual/en/language.types.array.php#language.types.array.casting
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
$_kintSettings['devel'] = true;

unset( $_kintSettings );


/**
 * This is used with PHPstorm and its remotecall plugin to click file links in browser that will open the IDE in the
 * appropriate place in code.
 *
 * @param string $file
 * @param int    $line
 *
 * @return string
 */
function _kintLine( $file, $line = NULL )
{
	$shortenedName = error::debug_path( $file );

	if ( !$line ) { // means this is called from resource type dump
		return $shortenedName;
	}


	return "<u><a class=\"kint-ide-link\" href=\"http://localhost:8091/?message={$file}:{$line}\">"
		. $shortenedName
		. "</a></u> line <i>{$line}</i>";
}