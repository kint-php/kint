<?php
/**
 * Kint is a zero-setup debugging tool to output information about variables and stack traces prettily and comfortably.
 *
 * https://github.com/raveren/kint
 */
define( 'KINT_DIR', dirname( __FILE__ ) . '/' );

require KINT_DIR . 'config.default.php';
require KINT_DIR . 'parsers/parser.class.php';
require KINT_DIR . 'decorators/cli.php';
require KINT_DIR . 'decorators/rich.php';
require KINT_DIR . 'decorators/plain.php';
require KINT_DIR . 'decorators/whitespace.php';
require KINT_DIR . 'decorators/concise.php';

if ( is_readable( KINT_DIR . 'config.php' ) ) {
	require KINT_DIR . 'config.php';
}

# init settings
if ( !empty( $GLOBALS['_kint_settings'] ) ) {
	foreach ( $GLOBALS['_kint_settings'] as $key => $val ) {
		property_exists( 'Kint', $key ) and Kint::$$key = $val;
	}

	unset( $GLOBALS['_kint_settings'] );
}

if ( PHP_SAPI === 'cli' ) {
	Kint::$_detected = 'cli';
} elseif ( Kint::$ajaxDetection && isset( $_SERVER['HTTP_X_REQUESTED_WITH'] )
	&& strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) === 'xmlhttprequest'
) {
	Kint::$_detected = 'ajax';
}

if ( Kint::$ajaxDetection && Kint::$_detected !== 'ajax' && Kint::$_detected !== 'cli' ) {
	register_shutdown_function( 'Kint::_ajaxHandler' );
}

class Kint
{
	// these are all public and 1:1 config array keys so you can switch them easily
	public static $traceCleanupCallback;
	public static $fileLinkFormat;
	public static $hideSequentialKeys;
	public static $showClassConstants;
	public static $keyFilterCallback;
	public static $displayCalledFrom;
	public static $charEncodings;
	public static $maxStrLength;
	public static $appRootDirs;
	public static $maxLevels;
	public static $enabled;
	public static $theme;
	public static $expandedByDefault;

	public static $cliDetection;
	public static $cliColors;
	public static $ajaxDetection;

	private static $_isAjax;
	public static $_detected;

	/** @var  string cli|plain|whitespace|rich */
	public static $mode = 'rich';

	public static $aliases = array(
		'methods'   => array(
			array( 'kint', 'dump' ),
			array( 'kint', 'trace' ),
		),
		'functions' => array(
			'd',
			'dd',
			's',
			'sd',
		)
	);

	protected static $_firstRun = true;
	protected static $_headerNo = 0;

	# non-standard function calls
	protected static $_statements = array( 'include', 'include_once', 'require', 'require_once' );

	/**
	 * getter/setter for the enabled parameter, called at the beginning of every public function as getter, also
	 * initializes the settings if te first time it's run.
	 *
	 * @param null $value
	 *
	 * @return void|bool
	 */
	public static function enabled( $value = null )
	{
		# act both as a setter...
		if ( func_num_args() > 0 ) {
			self::$enabled = $value;
			return;
		}

		# ...and a getter
		return self::$enabled;
	}

	/**
	 * Prints a debug backtrace, same as `Kint::dump(1)`
	 *
	 * @param array $trace [OPTIONAL] you can pass your own trace, otherwise, `debug_backtrace` will be called
	 *
	 * @return void
	 */
	public static function trace( $trace = null )
	{
		if ( !Kint::enabled() ) return;

		return self::dump( isset( $trace ) ? $trace : debug_backtrace( true ) );
	}


	/**
	 * Dump information about variables, accepts any number of parameters, supports modifiers:
	 *
	 *  clean up any output before kint and place the dump at the top of page:
	 *   - Kint::dump()
	 *  *****
	 *  expand all nodes on display:
	 *   ! Kint::dump()
	 *  *****
	 *  dump variables disregarding their depth:
	 *   + Kint::dump()
	 *  *****
	 *  return output instead of displaying it (also disables ajax/cli detection):
	 *   @ Kint::dump()
	 *  *****
	 *  disable ajax and cli auto-detection and just output as requested (plain/rich):
	 *   ~ Kint::dump()
	 *
	 * Modifiers are supported by all dump wrapper functions, including Kint::trace(). Space is optional.
	 *
	 *
	 * You can also use the following shorthand to display debug_backtrace():
	 *   Kint::dump( 1 );
	 *
	 * Passing the result from debug_backtrace() to kint::dump() as a single parameter will display it as trace too:
	 *   $trace = debug_backtrace( true );
	 *   Kint::dump( $trace );
	 *  Or simply:
	 *   Kint::dump( debug_backtrace() );
	 *
	 *
	 * @param mixed $data
	 *
	 * @return void|string
	 */
	public static function dump( $data = null )
	{
		if ( !Kint::enabled() ) return '';

		# find caller information
		list( $names, $modifiers, $callee, $previousCaller, $miniTrace ) = self::_getPassedNames(
			defined( 'DEBUG_BACKTRACE_IGNORE_ARGS' )
				? debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS )
				: debug_backtrace()
		);

		# process modifiers: @, +, !, ~ and -
		if ( strpos( $modifiers, '-' ) !== false ) {
			self::$_firstRun = true;
			while ( ob_get_level() ) {
				ob_end_clean();
			}
		}
		if ( strpos( $modifiers, '!' ) !== false ) {
			$expandedByDefaultOldValue = self::$expandedByDefault;
			self::$expandedByDefault   = true;
		}
		if ( strpos( $modifiers, '+' ) !== false ) {
			$maxLevelsOldValue = self::$maxLevels;
			self::$maxLevels   = false;
		}
		if ( strpos( $modifiers, '@' ) !== false ) {
			$firstRunOldValue = self::$_firstRun;
			self::$_firstRun  = true;
		}
		# disable mode detection
		if ( strpos( $modifiers, '@' ) !== false || strpos( $modifiers, '~' ) === false ) {
			$modeOldValue   = self::$mode;
			$isAjaxOldValue = self::$_isAjax;
			if ( self::$_detected === 'ajax' ) {
				self::$_isAjax = true;
			} elseif ( self::$_detected === 'cli' && self::$cliDetection ) {
				# cli detection is checked here as you can toggle the feature for individual dumps
				self::$mode = self::$cliColors ? 'cli' : 'whitespace';
			}
		}


		$decoratorsMap = array(
			'cli'        => 'Kint_Decorators_Cli',
			'plain'      => 'Kint_Decorators_Plain',
			'rich'       => 'Kint_Decorators_Rich',
			'whitespace' => 'Kint_Decorators_Whitespace',
		);
		$decorator     = $decoratorsMap[self::$mode];
		$output        = $decorator::wrapStart( $callee );

		$trace = false;
		if ( $names === array( null ) && func_num_args() === 1 && $data === 1 ) {
			$trace = debug_backtrace( true ); # Kint::dump(1) shorthand
		} elseif ( func_num_args() === 1 && is_array( $data ) ) {
			$trace = $data; # test if the single parameter is result of debug_backtrace()
		}
		$trace and $trace = self::_parseTrace( $trace );

		if ( $trace ) {
			$output .= $decorator::decorateTrace( $trace );
		} else {
			$data = func_num_args() === 0
				? array( "[[no arguments passed]]" )
				: func_get_args();

			foreach ( $data as $k => $argument ) {
				kintParser::reset();
				$output .= $decorator::decorate( kintParser::factory( $argument, $names[$k] ) );
			}
		}


		$output .= $decorator::wrapEnd( $callee, $miniTrace, $previousCaller );

		if ( strpos( $modifiers, '~' ) === false ) {
			self::$mode = $modeOldValue;
		}
		if ( strpos( $modifiers, '!' ) !== false ) {
			self::$expandedByDefault = $expandedByDefaultOldValue;
		}
		if ( strpos( $modifiers, '+' ) !== false ) {
			self::$maxLevels = $maxLevelsOldValue;
		}
		if ( strpos( $modifiers, '@' ) !== false ) {
			self::$_firstRun = $firstRunOldValue;
			return $output;
		}

		if ( self::$_isAjax ) {
			$data   = rawurlencode( $output );
			$chunks = array();

			while ( strlen( $data ) > 4096 ) {
				$chunks[] = substr( $data, 0, 4096 );
				$data     = substr( $data, 4096 );
			}
			$chunks[] = $data;

			for ( $i = 0, $c = count( $chunks ); $i < $c; $i++ ) {
				$index = self::$_headerNo++;
				$name  = 'kint' . ( $index > 0 ? "-$index" : '' );
				header( "$name: {$chunks[$i]}" );
			}

			if ( strpos( $modifiers, '~' ) === false ) {
				self::$_isAjax = $isAjaxOldValue;
			}
			return '';
		}

		echo $output;
		return '';
	}


	/**
	 * generic path display callback, can be configured in the settings; purpose is to show relevant path info and hide
	 * as much of the path as possible.
	 *
	 * @param string $file
	 * @param int    $line [OPTIONAL]
	 * @param bool   $wrapInHtml
	 *
	 * @return string
	 */
	public static function shortenPath( $file, $line = null, $wrapInHtml = true )
	{
		$file          = str_replace( '\\', '/', $file );
		$shortenedName = $file;
		$replaced      = false;
		if ( is_array( self::$appRootDirs ) ) foreach ( self::$appRootDirs as $path => $replaceString ) {
			if ( empty( $path ) ) continue;

			$path = str_replace( '\\', '/', $path );

			if ( strpos( $file, $path ) === 0 ) {
				$shortenedName = $replaceString . substr( $file, strlen( $path ) );
				$replaced      = true;
				break;
			}
		}

		if ( !$replaced ) {
			$pathParts = explode( '/', str_replace( '\\', '/', KINT_DIR ) );
			$fileParts = explode( '/', $file );
			$i         = 0;
			foreach ( $fileParts as $i => $filePart ) {
				if ( !isset( $pathParts[$i] ) || $pathParts[$i] !== $filePart ) break;
			}
			$shortenedName = '.../' . implode( '/', array_slice( $fileParts, $i ) );
		}


		if ( !$line ) { # means this is called from resource type dump
			return $shortenedName;
		}

		if ( !self::$fileLinkFormat ) {
			return "{$shortenedName}:{$line}";
		}

		$url = str_replace( array( '%f', '%l' ), array( $file, $line ), self::$fileLinkFormat );

		if ( $wrapInHtml ) {
			$class = ( strpos( $url, 'http://' ) === 0 ) ? 'class="kint-ide-link" ' : '';
			return "<a {$class}href=\"{$url}\">{$shortenedName}:{$line}</a>";
		} else {
			return array( $url, $shortenedName . ':' . $line );
		}
	}

	/**
	 * trace helper, shows the place in code inline
	 *
	 * @param string $file full path to file
	 * @param int    $lineNumber the line to display
	 * @param int    $padding surrounding lines to show besides the main one
	 *
	 * @return bool|string
	 */
	private static function _showSource( $file, $lineNumber, $padding = 7 )
	{
		if ( !$file OR !is_readable( $file ) ) {
			# continuing will cause errors
			return false;
		}

		# open the file and set the line position
		$file = fopen( $file, 'r' );
		$line = 0;

		# Set the reading range
		$range = array(
			'start' => $lineNumber - $padding,
			'end'   => $lineNumber + $padding
		);

		# set the zero-padding amount for line numbers
		$format = '% ' . strlen( $range['end'] ) . 'd';

		$source = '';
		while ( ( $row = fgets( $file ) ) !== false ) {
			# increment the line number
			if ( ++$line > $range['end'] ) {
				break;
			}

			if ( $line >= $range['start'] ) {
				# make the row safe for output
				$row = htmlspecialchars( $row, ENT_NOQUOTES, 'UTF-8' );

				# trim whitespace and sanitize the row
				$row = '<span>' . sprintf( $format, $line ) . '</span> ' . $row;

				if ( $line === $lineNumber ) {
					# apply highlighting to this row
					$row = '<div class="kint-highlight">' . $row . '</div>';
				} else {
					$row = '<div>' . $row . '</div>';
				}

				# add to the captured source
				$source .= $row;
			}
		}

		# close the file
		fclose( $file );

		return $source;
	}


	/**
	 * returns parameter names that the function was passed, as well as any predefined symbols before function
	 * call (modifiers)
	 *
	 * @param array $trace
	 *
	 * @return array( $parameters, $modifier, $callee, $previousCaller )
	 */
	private static function _getPassedNames( $trace )
	{
		list( $previousCaller, $miniTrace, $callee ) = self::_getCallerInfo( $trace );

		if ( !isset( $callee['file'] ) || !is_readable( $callee['file'] ) ) {
			return false;
		}

		# open the file and read it up to the position where the function call expression ended
		$file   = fopen( $callee['file'], 'r' );
		$line   = 0;
		$source = '';
		while ( ( $row = fgets( $file ) ) !== false ) {
			if ( ++$line > $callee['line'] ) break;
			$source .= $row;
		}
		fclose( $file );
		$source = self::_removeAllButCode( $source );


		$codePattern = empty( $callee['class'] )
			? $callee['function']
			: $callee['class'] . "\x07*" . $callee['type'] . "\x07*" . $callee['function'];


		# get the position of the last call to the function
		preg_match_all(
			$r = "~
			# beginning of statement
			[\x07{(]

			# search for modifiers (group 1)
			([-+!@\\~]*)?

			# spaces, spaces everywhere
			\x07*

			# check if output is assigned to a variable (group 2)
			(
				\\$[a-z0-9_]+ # variable
				\x07*\\.?=\x07*  # assignment
			)?

			# possibly a namespace symbol
			\\\\?

			\x07*

			# main call to Kint
			{$codePattern}

			\x07*

			# find the character where kint's opening bracket resides (group 3)
			(\\()

			~ix",
			$source,
			$matches,
			PREG_OFFSET_CAPTURE
		);

		$modifiers  = end( $matches[1] );
		$assignment = end( $matches[2] );
		$bracket    = end( $matches[3] );

		$modifiers = $modifiers[0];
		if ( $assignment[1] !== -1 ) {
			$modifiers .= '@';
		}

		$passedParameters = preg_replace( "[\x07+]", ' ', substr( $source, $bracket[1] + 1 ) );
		# we now have a string like this:
		# <parameters passed>); <the rest of the last read line>

		# remove everything in brackets and quotes, we don't need nested statements nor literal strings which would
		# only complicate separating individual arguments
		$c          = strlen( $passedParameters );
		$inString   = $escaped = false;
		$i          = 0;
		$inBrackets = 0;
		while ( $i < $c ) {
			$letter = $passedParameters[$i];
			if ( $inString === false ) {
				if ( $letter === '\'' || $letter === '"' ) {
					$inString = $letter;
				} elseif ( $letter === '(' ) {
					$inBrackets++;
				} elseif ( $letter === ')' ) {
					$inBrackets--;
					if ( $inBrackets === -1 ) { # this means we are out of the brackets that denote passed parameters
						$passedParameters = substr( $passedParameters, 0, $i );
						break;
					}
				}
			} elseif ( $letter === $inString && !$escaped ) {
				$inString = false;
			}

			# place an untype-able character instead of whatever was inside quotes or brackets, we don't
			# need that info. We'll later replace it with '...'
			if ( $inBrackets > 0 ) {
				if ( $inBrackets > 1 || $letter !== '(' ) {
					$passedParameters[$i] = "\x07";
				}
			}
			if ( $inString !== false ) {
				if ( $letter !== $inString || $escaped ) {
					$passedParameters[$i] = "\x07";
				}
			}

			$escaped = !$escaped && ( $letter === '\\' );
			$i++;
		}

		# by now we have an unnested arguments list, lets make it to an array for processing further
		$arguments = explode( ',', preg_replace( "~\x07+~", '...', $passedParameters ) );

		# test each argument whether it was passed literary or was it an expression or a variable name
		$parameters = array();
		$blacklist  = array( 'null', 'true', 'false', 'array(...)', 'array()', '"..."', 'b"..."', );
		foreach ( $arguments as $argument ) {
			$argument = trim( $argument );

			if ( is_numeric( $argument )
				|| in_array( str_replace( "'", '"', strtolower( $argument ) ), $blacklist, true )
			) {
				$parameters[] = null;
			} else {
				$parameters[] = $argument;
			}
		}

		return array( $parameters, $modifiers, $callee, $previousCaller, $miniTrace );
	}

	/**
	 * removes comments and zaps whitespace & <?php tags from php code, makes for easier further parsing
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	private static function _removeAllButCode( $source )
	{
		$newStr        = '';
		$tokens        = token_get_all( $source );
		$commentTokens = array( T_COMMENT => true, T_INLINE_HTML => true, T_DOC_COMMENT => true );

		defined( 'T_NS_SEPARATOR' ) or define( 'T_NS_SEPARATOR', 380 );

		$whiteSpaceTokens = array(
			T_WHITESPACE => true, T_CLOSE_TAG => true,
			T_OPEN_TAG   => true, T_OPEN_TAG_WITH_ECHO => true,
		);

		foreach ( $tokens as $token ) {
			if ( is_array( $token ) ) {
				if ( isset( $commentTokens[$token[0]] ) ) continue;

				if ( $token[0] === T_NEW ) {
					$token = 'new ';
				} elseif ( $token[0] === T_NS_SEPARATOR ) {
					$token = "\\\x07";
				} elseif ( isset( $whiteSpaceTokens[$token[0]] ) ) {
					$token = "\x07";
				} else {
					$token = $token[1];
				}
			} elseif ( $token === ';' ) {
				$token = "\x07";
			}

			$newStr .= $token;
		}
		return $newStr;
	}

	/**
	 * @param $trace
	 *
	 * @return array
	 */
	private static function _getCallerInfo( $trace )
	{
		$previousCaller = array();
		$miniTrace      = array();
		$prevStep       = array();

		# go from back of trace to find first occurrence of call to Kint or its wrappers
		while ( $step = array_pop( $trace ) ) {

			if ( self::_stepIsInternal( $step ) ) {
				$previousCaller = $prevStep;
				break;
			} elseif ( isset( $step['file'], $step['line'] ) ) {
				unset( $step['object'], $step['args'] );
				array_unshift( $miniTrace, $step );
			}

			$prevStep = $step;
		}

		return array( $previousCaller, $miniTrace, $step );
	}

	/**
	 * returns whether current trace step belongs to Kint or its wrappers
	 *
	 * @param $step
	 *
	 * @return array
	 */
	private static function _stepIsInternal( $step )
	{
		if ( isset( $step['class'] ) ) {
			foreach ( self::$aliases['methods'] as $alias ) {
				if ( $alias[0] === strtolower( $step['class'] ) && $alias[1] === strtolower( $step['function'] ) ) {
					return true;
				}
			}
			return false;
		} else {
			return in_array( strtolower( $step['function'] ), self::$aliases['functions'], true );
		}
	}

	private static function _parseTrace( array $data )
	{
		$trace          = array();
		$tracePrototype = array( 'file', 'line', 'args', 'class' );
		while ( $step = array_pop( $data ) ) {
			if ( !is_array( $step ) ) return false;

			$valid = false; # this method also validates whether a trace was indeed passed
			if ( sizeof( $step ) <= 7 ) {
				$found = 0;
				foreach ( $tracePrototype as $element ) {
					if ( isset( $step[$element] ) ) {
						if ( ++$found === 2 ) {
							$valid = true;
							break;
						}
					}
				}

				if ( !$valid && isset( $step['function'] )
					&& substr( $step['function'], -9, 9 ) === '{closure}'
				) {
					$valid = true;
				}
			}

			if ( !$valid ) return false;

			if ( self::_stepIsInternal( $step ) ) {
				$step = array(
					'file'     => $step['file'],
					'line'     => $step['line'],
					'function' => '',
				);
				array_unshift( $trace, $step );
				break;
			}
			array_unshift( $trace, $step );
		}

		$output = array();
		foreach ( $trace as $step ) {
			if ( isset( self::$traceCleanupCallback ) ) {
				$step = call_user_func( self::$traceCleanupCallback, $step );

				# if the user defined trace cleanup function returns null, skip this line
				if ( $step === null ) {
					continue;
				}
			}

			if ( isset( $step['file'] ) ) {
				$file = $step['file'];

				if ( isset( $step['line'] ) ) {
					$line = $step['line'];
					# include the source of this step
					if ( self::$mode === 'rich' ) {
						$source = self::_showSource( $file, $line );
					}
				}
			}

			# still, might be not a trace
			if ( !isset( $step['function'] ) ) return false;


			$function = $step['function'];

			if ( in_array( $function, self::$_statements ) ) { # include, require
				if ( empty( $step['args'] ) ) {
					# no arguments
					$args = array();
				} else {
					# sanitize the file path
					$args = array( self::shortenPath( $step['args'][0] ) );
				}
			} elseif ( isset( $step['args'] ) ) {
				if ( empty( $step['class'] ) && !function_exists( $function ) ) {
					# introspection on closures or language constructs in a stack trace is impossible before PHP 5.3
					$params = null;
				} else {
					try {
						if ( isset( $step['class'] ) ) {
							if ( method_exists( $step['class'], $function ) ) {
								$reflection = new ReflectionMethod( $step['class'], $function );
							} else if ( isset( $step['type'] ) && $step['type'] == '::' ) {
								$reflection = new ReflectionMethod( $step['class'], '__callStatic' );
							} else {
								$reflection = new ReflectionMethod( $step['class'], '__call' );
							}
						} else {
							$reflection = new ReflectionFunction( $function );
						}

						# get the function parameters
						$params = $reflection->getParameters();
					} catch ( Exception $e ) { # avoid various PHP version incompatibilities
						$params = null;
					}
				}

				$args = array();
				foreach ( $step['args'] as $i => $arg ) {
					if ( isset( $params[$i] ) ) {
						# assign the argument by the parameter name
						$args[$params[$i]->name] = $arg;
					} else {
						# assign the argument by number
						$args['#' . ( $i + 1 )] = $arg;
					}
				}
			}

			if ( isset( $step['class'] ) ) {
				# Class->method() or Class::method()
				$function = $step['class'] . $step['type'] . $function;
			}

			$output[] = array(
				'function' => $function,
				'args'     => isset( $args ) ? $args : null,
				'file'     => isset( $file ) ? $file : null,
				'line'     => isset( $line ) ? $line : null,
				'source'   => isset( $source ) ? $source : null,
				'object'   => isset( $step['object'] ) ? $step['object'] : null,
			);

			unset( $function, $args, $file, $line, $source );
		}

		return $output;
	}

	public static function _ajaxHandler()
	{
		if ( !Kint::$enabled ) return;

		# if content type is not HTML (e.g. csv export) and/or is downloaded - skip
		foreach ( headers_list() as $header ) {
			if ( substr( strtolower( $header ), 0, 13 ) === "content-type:" ) {
				if ( strpos( $header, 'text/html' ) === false ) {
					return;
				}
			} elseif ( substr( strtolower( $header ), 0, 20 ) === "content-disposition:" ) {
				return;
			}
		}

		$baseDir = KINT_DIR . 'view/compiled/modular-window/';

		if ( !is_readable( $cssFile = $baseDir . Kint::$theme . '.css' ) ) {
			$cssFile = $baseDir . 'original.css';
		}

		echo '<script>' . file_get_contents( $baseDir . 'kint.js' ) . '</script>'
			. '<style>' . file_get_contents( $cssFile ) . "</style>";
	}
}


if ( !function_exists( 'd' ) ) {
	/**
	 * Alias of Kint::dump()
	 *
	 * @return string
	 */
	function d()
	{
		if ( !Kint::enabled() ) return null;

		$args = func_get_args();
		return call_user_func_array( array( 'Kint', 'dump' ), $args );
	}
}

if ( !function_exists( 'dd' ) ) {
	/**
	 * Alias of Kint::dump()
	 * [!!!] IMPORTANT: execution will halt after call to this function
	 *
	 * @return string
	 */
	function dd()
	{
		if ( !Kint::enabled() ) return;

		$args = func_get_args();
		call_user_func_array( array( 'Kint', 'dump' ), $args );
		die;
	}
}

if ( !function_exists( 's' ) ) {

	/**
	 * Alias of kintLite()
	 *
	 * @return string
	 */
	function s()
	{
		if ( !Kint::enabled() ) return;
		$mode       = Kint::$mode;
		Kint::$mode = 'plain';
		$o          = call_user_func_array( 'Kint::dump', func_get_args() );
		Kint::$mode = $mode;
		return $o;
	}

	/**
	 * Alias of kintLite()
	 * [!!!] IMPORTANT: execution will halt after call to this function
	 *
	 * @return string
	 */
	function sd()
	{
		if ( !Kint::enabled() ) return;
		$mode       = Kint::$mode;
		Kint::$mode = 'plain';
		call_user_func_array( 'Kint::dump', func_get_args() );
		Kint::$mode = $mode;
		die;
	}

}
