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
	public static $devel; # todo remove

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

	public static function _init()
	{
		# init settings
		if ( isset( $GLOBALS['_kint_settings'] ) ) {
			foreach ( $GLOBALS['_kint_settings'] as $key => $val ) {
				self::$$key = $val;
			}
		}

		require KINT_DIR . 'decorators/rich.php';
		require KINT_DIR . 'decorators/concise.php';
	}

	/**
	 * Prints a debug backtrace
	 *
	 * @param array $trace [OPTIONAL] you can pass your own trace, otherwise, `debug_backtrace` will be called
	 *
	 * @return void
	 */
	public static function trace( $trace = null )
	{
		if ( !Kint::enabled() ) return;

		echo Kint_Decorators_Rich::_css();

		isset( $trace ) or $trace = debug_backtrace( true );

		$output = array();
		foreach ( $trace as $step ) {
			self::$traceCleanupCallback and $step = call_user_func( self::$traceCleanupCallback, $step );

			# if the user defined trace cleanup function returns null, skip this line
			if ( $step === null ) {
				continue;
			}

			if ( !isset( $step['function'] ) ) {
				# invalid trace step
				continue;
			}

			if ( isset( $step['file'] ) AND isset( $step['line'] ) ) {
				# include the source of this step
				$source = self::_showSource( $step['file'], $step['line'] );
			}

			if ( isset( $step['file'] ) ) {
				$file = $step['file'];

				if ( isset( $step['line'] ) ) {
					$line = $step['line'];
				}
			}


			$function = $step['function'];

			if ( in_array( $step['function'], self::$_statements ) ) {
				if ( empty( $step['args'] ) ) {
					# no arguments
					$args = array();
				} else {
					# sanitize the file path
					$args = array( self::shortenPath( $step['args'][0] ) );
				}
			} elseif ( isset( $step['args'] ) ) {
				if ( empty( $step['class'] ) && !function_exists( $step['function'] ) ) {
					# introspection on closures or language constructs in a stack trace is impossible before PHP 5.3
					$params = null;
				} else {
					try {
						if ( isset( $step['class'] ) ) {
							if ( method_exists( $step['class'], $step['function'] ) ) {
								$reflection = new ReflectionMethod( $step['class'], $step['function'] );
							} else if ( isset( $step['type'] ) && $step['type'] == '::' ) {
								$reflection = new ReflectionMethod( $step['class'], '__callStatic' );
							} else {
								$reflection = new ReflectionMethod( $step['class'], '__call' );
							}
						} else {
							$reflection = new ReflectionFunction( $step['function'] );
						}

						# get the function parameters
						$params = $reflection->getParameters();
					} catch ( Exception $e ) {
						$params = null; # avoid various PHP version incompatibilities
					}
				}

				$args = array();
				foreach ( $step['args'] as $i => $arg ) {
					if ( isset( $params[$i] ) ) {
						# assign the argument by the parameter name
						$args[$params[$i]->name] = $arg;
					} else {
						# assign the argument by number
						$args[$i] = $arg;
					}
				}
			}

			if ( isset( $step['class'] ) ) {
				# Class->method() or Class::method()
				$function = $step['class'] . $step['type'] . $step['function'];
			}

			if ( isset( $step['object'] ) ) {
				$function = $step['class'] . $step['type'] . $step['function'];
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

		require KINT_DIR . 'view/trace.phtml';
	}

	/**
	 * dump information about variables
	 *
	 * @param mixed $data
	 *
	 * @return void|string
	 */
	public static function dump( $data = null )
	{
		if ( !Kint::enabled() ) return;

		# find caller information
		$trace = debug_backtrace();
		list( $names, $modifier, $callee, $previousCaller ) = self::_getPassedNames( $trace );
		if ( $names === array( null ) && func_num_args() === 1 && $data === 1 ) {
			$call = reset( $trace );
			if ( !isset( $call['file'] ) && isset( $call['class'] ) && $call['class'] === __CLASS__ ) {
				array_shift( $trace );
				$call = reset( $trace );
			}

			while ( isset( $call['file'] ) && $call['file'] === __FILE__ ) {
				array_shift( $trace );
				$call = reset( $trace );
			}

			self::trace( $trace );
			return;
		}

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


		$data = func_num_args() === 0
			? array( "[[no arguments passed]]" )
			: func_get_args();


		$output = Kint_Decorators_Rich::_css();
		$output .= Kint_Decorators_Rich::_wrapStart( $callee );

		foreach ( $data as $k => $argument ) {
			$output .= self::_dump( $argument, $names[$k] );
		}
		$output .= Kint_Decorators_Rich::_wrapEnd( $callee, $previousCaller );

		self::$_firstRun = false;

		switch ( $modifier ) {
			case '+':
				self::$maxLevels = $maxLevelsOldValue;
				echo $output;
				break;
			case '@':
				self::$_firstRun = $firstRunOldValue;
				return $output;
				break;
			default:
				echo $output;
				break;
		}

		return '';
	}

	protected static function _dump( $var, $name = '' )
	{
		kintParser::reset();
		return Kint_Decorators_Rich::decorate(
			kintParser::factory( $var, $name )
		);
	}


	/**
	 * generic path display callback, can be configured in the settings
	 *
	 * @param string $file
	 * @param int    $line [OPTIONAL]
	 *
	 * @return string
	 */
	public static function shortenPath( $file, $line = null )
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

		$url   = str_replace( array( '%f', '%l' ), array( $file, $line ), self::$fileLinkFormat );
		$class = ( strpos( $url, 'http://' ) === 0 ) ? 'class="kint-ide-link"' : '';

		return "<u><a {$class} href=\"{$url}\">{$shortenedName}</a></u> line <i>{$line}</i>";
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
		$previousCaller = array();
		while ( $callee = array_pop( $trace ) ) {
			if ( strtolower( $callee['function'] ) === 'd' ||
				strtolower( $callee['function'] ) === 'dd' ||
				( isset( $callee['class'] ) && strtolower( $callee['class'] ) === strtolower( __CLASS__ )
					&& strtolower( $callee['function'] ) === 'dump' )
			) {
				break;
			} else {
				$previousCaller = $callee;
			}
		}

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
		preg_match_all( "#[\x07{(](\\+|-|!|@)?{$codePattern}\x07*(\\()#i", $source, $matches, PREG_OFFSET_CAPTURE );
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

		$argv = func_get_args();
		echo '<pre>';
		foreach ( $argv as $k => $v ) {
			$k && print( "\n\n" );
			echo kintLite( $v );
		}
		echo '</pre>' . "\n";
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

		echo '<pre>';
		foreach ( func_get_args() as $k => $v ) {
			$k && print( "\n\n" );
			echo kintLite( $v );
		}
		echo '</pre>';
		die;

	}

}

if (!function_exists('kintLite')) {
/**
 * lightweight version of Kint::dump(). Uses whitespace for formatting instead of html
 * sadly not DRY yet
 *
 * @param     $var
 * @param int $level
 *
 * @return string
 */
function kintLite( &$var, $level = 0 )
{

	// initialize function names into variables for prettier string output (html and implode are also DRY)
	$html     = "htmlspecialchars";
	$implode  = "implode";
	$strlen   = "strlen";
	$count    = "count";
	$getClass = "get_class";


	if ( $var === null ) {
		return 'NULL';
	} elseif ( is_bool( $var ) ) {
		return 'bool ' . ( $var ? 'TRUE' : 'FALSE' );
	} elseif ( is_float( $var ) ) {
		return 'float ' . $var;
	} elseif ( is_int( $var ) ) {
		return 'integer ' . $var;
	} elseif ( is_resource( $var ) ) {
		if ( ( $type = get_resource_type( $var ) ) === 'stream' AND $meta = stream_get_meta_data( $var ) ) {

			if ( isset( $meta['uri'] ) ) {
				$file = $meta['uri'];

				return "resource ({$type}) {$html( $file, 0 )}";
			} else {
				return "resource ({$type})";
			}
		} else {
			return "resource ({$type})";
		}
	} elseif ( is_string( $var ) ) {
		return "string ({$strlen( $var )}) \"{$html( $var )}\"";
	} elseif ( is_array( $var ) ) {
		$output = array();
		$space  = str_repeat( $s = '    ', $level );

		static $marker;

		if ( $marker === null ) {
			// Make a unique marker
			$marker = uniqid( "\x00" );
		}

		if ( empty( $var ) ) {
			return "array()";
		} elseif ( isset( $var[$marker] ) ) {
			$output[] = "[\n$space$s*RECURSION*\n$space]";
		} elseif ( $level < 7 ) {
			$isSeq = array_keys( $var ) === range( 0, count( $var ) - 1 );

			$output[] = "[";

			$var[$marker] = true;


			foreach ( $var as $key => &$val ) {
				if ( $key === $marker ) continue;

				$key = $space . $s . ( $isSeq ? "" : "'{$html( $key, 0 )}' => " );

				$dump     = kintLite( $val, $level + 1 );
				$output[] = "{$key}{$dump}";
			}

			unset( $var[$marker] );
			$output[] = "$space]";

		} else {
			$output[] = "[\n$space$s*depth too great*\n$space]";
		}
		return "array({$count( $var )}) {$implode( "\n", $output )}";
	} elseif ( is_object( $var ) ) {
		if ( $var instanceof SplFileInfo ) {
			return "object SplFileInfo " . $var->getRealPath();
		}

		// Copy the object as an array
		$array = (array)$var;

		$output = array();
		$space  = str_repeat( $s = '    ', $level );

		$hash = spl_object_hash( $var );

		// Objects that are being dumped
		static $objects = array();

		if ( empty( $array ) ) {
			return "object {$getClass( $var )} {}";
		} elseif ( isset( $objects[$hash] ) ) {
			$output[] = "{\n$space$s*RECURSION*\n$space}";
		} elseif ( $level < 7 ) {
			$output[]       = "{";
			$objects[$hash] = true;

			foreach ( $array as $key => & $val ) {
				if ( $key[0] === "\x00" ) {

					$access = $key[1] === "*" ? "protected" : "private";

					// Remove the access level from the variable name
					$key = substr( $key, strrpos( $key, "\x00" ) + 1 );
				} else {
					$access = "public";
				}

				$output[] = "$space$s$access $key -> " . kintLite( $val, $level + 1 );
			}
			unset( $objects[$hash] );
			$output[] = "$space}";

		} else {
			$output[] = "{\n$space$s*depth too great*\n$space}";
		}

		return "object {$getClass( $var )} ({$count( $array )}) {$implode( "\n", $output )}";
	} else {
		return gettype( $var ) . htmlspecialchars( var_export( $var, true ), ENT_NOQUOTES );
	}
}
}
Kint::_init();
