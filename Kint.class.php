<?php
/**
 * Kint is a zero-setup debugging tool to output information about variables and stack traces prettily and comfortably.
 *
 * https://github.com/raveren/kint
 */
define( 'KINT_DIR', dirname( __FILE__ ) . '/' );
require KINT_DIR . 'config.default.php';
require KINT_DIR . 'parsers/parser.class.php';

if ( is_readable( KINT_DIR . 'config.php' ) ) {
	require KINT_DIR . 'config.php';
}

# init settings
if ( isset( $GLOBALS['_kint_settings'] ) ) {
	foreach ( $GLOBALS['_kint_settings'] as $key => $val ) {
		property_exists( 'Kint', $key ) and Kint::$$key = $val;
	}
}

require KINT_DIR . 'decorators/rich.php';
require KINT_DIR . 'decorators/plain.php';
require KINT_DIR . 'decorators/concise.php';


class Kint
{
	// these are all public and 1:1 config array keys so you can switch them easily
	public static $traceCleanupCallback;
	public static $fileLinkFormat;
	public static $hideSequentialKeys;
	public static $showClassConstants;
	public static $keyFilterCallback;
	public static $displayCalledFrom;
	public static $textOnly;
	public static $charEncodings;
	public static $maxStrLength;
	public static $appRootDirs;
	public static $maxLevels;
	public static $enabled;
	public static $theme;
	public static $expandedByDefault;

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
	 *  return output instead of displaying it:
	 *   @ Kint::dump()
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
		$trace = debug_backtrace();
		list( $names, $modifier, $callee, $previousCaller ) = self::_getPassedNames( $trace );

		# process modifiers: @, + and -
		switch ( $modifier ) {
			case '-':
				self::$_firstRun = true;
				while ( ob_get_level() ) {
					ob_end_clean();
				}
				break;
			case '!':
				self::$expandedByDefault = true;
				break;
			case '+':
				$maxLevelsOldValue = self::$maxLevels;
				self::$maxLevels   = false;
				break;
			case '@':
				$firstRunOldValue = self::$_firstRun;
				self::$_firstRun  = true;
				break;
		}


		if ( self::$textOnly ) {
			$output = Kint_Decorators_Plain::wrapStart( $callee );
		} else {
			$output = Kint_Decorators_Rich::wrapStart( $callee );
		}


		$trace = false;
		if ( $names === array( null ) && func_num_args() === 1 && $data === 1 ) {
			$trace = debug_backtrace( true ); # Kint::dump(1) shorthand
		} elseif ( func_num_args() === 1 && is_array( $data ) ) {
			$trace = $data; # test if the single parameter is result of debug_backtrace()
		}

		if ( $trace ) {
			$trace = self::_parseTrace( $trace );

			if ( self::$textOnly ) {
				$output .= Kint_Decorators_Plain::decorateTrace( $trace );
			} else {
				$output .= Kint_Decorators_Rich::decorateTrace( $trace );
				self::$_firstRun = false;
			}
		} else {
			$data = func_num_args() === 0
				? array( "[[no arguments passed]]" )
				: func_get_args();

			foreach ( $data as $k => $argument ) {
				$output .= self::_dump( $argument, $names[$k] );
			}
		}


		if ( self::$textOnly ) {
			$output .= Kint_Decorators_Plain::wrapEnd( $callee, $previousCaller );
		} else {
			$output .= Kint_Decorators_Rich::wrapEnd( $callee, $previousCaller );
			self::$_firstRun = false;
		}

		switch ( $modifier ) {
			case '+':
				self::$maxLevels = $maxLevelsOldValue;
				echo $output;
				break;
			case '@':
				self::$_firstRun = $firstRunOldValue;
				return $output;
				break;
			case '!':
				self::$expandedByDefault = false;
				echo $output;
				break;
			default:
				echo $output;
				break;
		}

		return '';
	}


	/**
	 * generic path display callback, can be configured in the settings
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
		foreach ( self::$appRootDirs as $path => $replaceString ) {
			$path = str_replace( '\\', '/', $path );

			if ( strpos( $file, $path ) === 0 ) {
				$shortenedName = $replaceString . substr( $file, strlen( $path ) );
				break;
			}
		}


		if ( !$line ) { # means this is called from resource type dump
			return $shortenedName;
		}

		if ( !self::$fileLinkFormat ) {
			return "{$shortenedName} line <i>{$line}</i>";
		}

		$url = str_replace( array( '%f', '%l' ), array( $file, $line ), self::$fileLinkFormat );

		if ( $wrapInHtml ) {
			$class = ( strpos( $url, 'http://' ) === 0 ) ? 'class="kint-ide-link"' : '';
			return "<u><a {$class} href=\"{$url}\">{$shortenedName}</a></u> line <i>{$line}</i>";
		} else {
			return array( $url, $shortenedName . ':' . $line );
		}
	}


	private static function _dump( $var, $name = '' )
	{
		kintParser::reset();
		if ( self::$textOnly ) {
			return Kint_Decorators_Plain::decorate(
				kintParser::factory( $var, $name )
			);
		}
		return Kint_Decorators_Rich::decorate(
			kintParser::factory( $var, $name )
		);
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
				$row = htmlspecialchars( $row, ENT_NOQUOTES );

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
		list( $previousCaller, $callee ) = self::_getCallerInfo( $trace );
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
		preg_match_all( "#[\x07{(](\\+|-|!|@)?\\\\?\x07*{$codePattern}\x07*(\\()#i", $source, $matches, PREG_OFFSET_CAPTURE );
		$match    = end( $matches[2] );
		$modifier = end( $matches[1] );
		$modifier = $modifier[0];

		$passedParameters = str_replace( "\x07", '', substr( $source, $match[1] + 1 ) );
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
		$arguments = explode( ',', preg_replace( "#\x07+#", '...', $passedParameters ) );

		# test each argument whether it was passed literary or was it an expression or a variable name
		$parameters = array();
		$blacklist  = array( 'null', 'true', 'false', 'array(...)', 'array()', '"..."', 'b"..."', );
		foreach ( $arguments as $argument ) {

			if ( is_numeric( $argument )
				|| in_array( str_replace( "'", '"', strtolower( $argument ) ), $blacklist, true )
			) {
				$parameters[] = null;
			} else {
				$parameters[] = trim( $argument );
			}
		}

		return array( $parameters, $modifier, $callee, $previousCaller );
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

		# go from back of trace to find first occurrence of call to Kint or its wrappers
		while ( $step = array_pop( $trace ) ) {
			if ( self::_stepIsInternal( $step ) ) {
				$previousCaller = array_pop( $trace );
				break;
			}
		}

		return array( $previousCaller, $step );
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
			foreach ( static::$aliases['methods'] as $alias ) {
				if ( $alias[0] === strtolower( $step['class'] ) && $alias[1] === strtolower( $step['function'] ) ) {
					return true;
				}
			}
			return false;
		} else {
			return in_array( strtolower( $step['function'] ), static::$aliases['functions'], true );
		}
	}

	private static function _parseTrace( array $data )
	{
		$trace = array();
		while ( $step = array_pop( $data ) ) {
			if ( !isset( $step['function'] ) ) {
				return false; # this method also validates whether a trace was indeed passed
			}

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
					self::$textOnly or $source = self::_showSource( $file, $line );
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
		Kint::$textOnly = true;
		$o              = call_user_func_array( 'Kint::dump', func_get_args() );
		Kint::$textOnly = false;
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
		Kint::$textOnly = true;
		call_user_func_array( 'Kint::dump', func_get_args() );
		Kint::$textOnly = false;
		die;
	}

}