<?php

class kintVariableData
{
	/** @var string */
	public $type;
	/** @var string */
	public $access;
	/** @var string */
	public $name;
	/** @var string */
	public $operator;
	/** @var string */
	public $subtype;
	/** @var int */
	public $size;
	/**
	 * @var kintVariableData[] array of kintVariableData objects or strings; displayed collapsed, each element from
	 * the array is a separate possible representation of the dumped var
	 */
	public $extendedValue;
	/** @var kintVariableData[] array of alternative representations for same variable */
	public $alternatives;
	/** @var string inline value */
	public $value;


	/* *******************************************
	 * HELPERS
	 */

	protected static function _escape( $value, $encoding = null )
	{
		$encoding or $encoding = self::_detectEncoding( $value );

		if ( $encoding === 'UTF-8' ) {
			# when possible force invisible characters to have some sort of display
			$value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '�', $value );
		}

		$value = htmlspecialchars( $value, ENT_QUOTES );
		if ( function_exists( 'mb_encode_numericentity' ) ) {
			return mb_encode_numericentity(
				$value,
				array( 0x80, 0xffff, 0, 0xffff, ),
				$encoding
			);
		} else {
			return $value;
		}
	}

	protected static function _detectEncoding( $value )
	{
		if ( function_exists( 'mb_detect_encoding' ) ) {
			$mbDetected = mb_detect_encoding( $value );
			if ( $mbDetected === 'ASCII' ) {
				return 'ASCII';
			}
		}

		if ( empty( Kint::$charEncodings ) || !function_exists( 'iconv' ) ) {
			return !empty( $mbDetected ) ? $mbDetected : 'UTF-8';
		}

		$md5 = md5( $value );
		foreach ( array_merge( array( 'UTF-8' ), Kint::$charEncodings ) as $encoding ) {
			# fuck knows why, //IGNORE and //TRANSLIT still throw notice
			if ( md5( @iconv( $encoding, $encoding, $value ) ) === $md5 ) {
				return $encoding;
			}
		}

		return 'ASCII';
	}

	/**
	 * zaps all excess whitespace from string, compacts it but hurts readability
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	protected static function _stripWhitespace( $string )
	{
		return preg_replace( '[\s+]', ' ', $string );
	}


	/**
	 * returns whether the array:
	 *  1) is numeric and
	 *  2) in sequence starting from zero
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	protected static function _isSequential( array $array )
	{
		return Kint::$hideSequentialKeys
			? array_keys( $array ) === range( 0, count( $array ) - 1 )
			: false;
	}

	protected static function _strlen( $string, $encoding = null )
	{
		$encoding or $encoding = self::_detectEncoding( $string );

		return function_exists( 'mb_strlen' )
			? mb_strlen( $string, $encoding )
			: strlen( $string );
	}

	protected static function _substr( $string, $end, $encoding = null )
	{
		$encoding or $encoding = self::_detectEncoding( $string );

		return function_exists( 'mb_substr' )
			? mb_substr( $string, 0, $end, $encoding )
			: substr( $string, 0, $end );
	}
}

abstract class kintParser extends kintVariableData
{
	private static $_level = 0;
	private static $_customDataTypes;
	private static $_objects;
	private static $_marker;

	private static $_skipAlternatives = false;



	private static function _init()
	{
		$fh = opendir( KINT_DIR . 'parsers/custom/' );
		while ( $fileName = readdir( $fh ) ) {
			if ( substr( $fileName, -4 ) !== '.php' ) continue;

			require KINT_DIR . 'parsers/custom/' . $fileName;
			self::$_customDataTypes[] = substr( $fileName, 0, -4 );
		}
	}

	public static function reset()
	{
		self::$_level   = 0;
		self::$_objects = self::$_marker = null;
	}

	/**
	 * main and usually single method a custom parser must implement
	 *
	 * @param mixed $variable
	 *
	 * @return mixed [!!!] false is returned if the variable is not of current type
	 */
	abstract protected function _parse( & $variable );


	/**
	 * the only public entry point to return a parsed representation of a variable
	 *
	 * @static
	 *
	 * @param      $variable
	 * @param null $name
	 *
	 * @throws Exception
	 * @return \kintParser
	 */
	public final static function factory( & $variable, $name = null )
	{
		isset( self::$_customDataTypes ) or self::_init();

		# save internal data to revert after dumping to properly handle recursions etc
		$revert = array(
			'level'   => self::$_level,
			'objects' => self::$_objects,
		);

		self::$_level++;

		$name          = self::_escape( $name );
		$varData       = new kintVariableData;
		$varData->name = $name;

		# first parse the variable based on its type
		$varType = gettype( $variable );
		$varType === 'unknown type' and $varType = 'unknown'; # PHP 5.4 inconsistency
		$methodName = '_parse_' . $varType;

		# base type parser returning false means "stop processing further": e.g. recursion
		if ( self::$methodName( $variable, $varData ) === false ) {
			self::$_level--;
			return $varData;
		}

		if ( !self::$_skipAlternatives) {
			# if an alternative returns something that can be represented in an alternative way, don't :)
			self::$_skipAlternatives = true;

			# now check whether the variable can be represented in a different way
			foreach ( self::$_customDataTypes as $parserClass ) {
				$className = 'Kint_Parsers_' . $parserClass;

				/** @var $object kintParser */
				$object       = new $className;
				$object->name = $name; # the parser may overwrite the name value, so set it first

				if ( $object->_parse( $variable ) !== false ) {
					$varData->alternatives[] = $object;
				}
			}


			# combine extended values with alternative representations if applicable
			if ( !empty( $varData->alternatives ) && isset( $varData->extendedValue ) ) {
				$a = new kintVariableData;

				$a->value = $varData->extendedValue;
				$a->type  = $varData->type;
				$a->size  = $varData->size;

				array_unshift( $varData->alternatives, $a );
				$varData->extendedValue = null;
			}

			self::$_skipAlternatives = false;
		}

		self::$_level   = $revert['level'];
		self::$_objects = $revert['objects'];
		return $varData;
	}

	private static function _checkDepth()
	{
		return Kint::$maxLevels != 0 && self::$_level > Kint::$maxLevels;
	}

	private static function _isArrayTabular( $variable )
	{
		foreach ( $variable as $row ) {
			if ( is_array( $row ) && !empty( $row ) ) {
				if ( isset( $keys ) ) {
					if ( $keys === array_keys( $row ) ) { // two rows have same keys in a row? Close enough.
						return true;
					}
				} else {

					foreach ( $row as $col ) {
						if ( !is_scalar( $col ) && $col !== null ) {
							break 2;
						}
					}

					$keys = array_keys( $row );
				}
			} else {
				break;
			}
		}

		return false;
	}

	private static $_dealingWithGlobals = false;

	private static function _parse_array( &$variable, kintVariableData $variableData )
	{
		isset( self::$_marker ) or self::$_marker = "\x00" . uniqid();

		# naturally, $GLOBALS variable is an intertwined recursion nightmare, use black magic
		$globalsDetector = false;
		if ( array_key_exists( 'GLOBALS', $variable ) && is_array( $variable['GLOBALS'] ) ) {
			$globalsDetector = "\x01" . uniqid();

			$variable['GLOBALS'][$globalsDetector] = true;
			if ( isset( $variable[$globalsDetector] ) ) {
				unset( $variable[$globalsDetector] );
				self::$_dealingWithGlobals = true;
			} else {
				unset( $variable['GLOBALS'][$globalsDetector] );
				$globalsDetector = false;
			}
		}

		$variableData->type = 'array';
		$variableData->size = count( $variable );

		if ( $variableData->size === 0 ) {
			return;
		}
		if ( isset( $variable[self::$_marker] ) ) { # recursion; todo mayhaps show from where
			if ( self::$_dealingWithGlobals ) {
				$variableData->value = '*RECURSION*';
			} else {
				unset( $variable[self::$_marker] );
				$variableData->value = self::$_marker;
			}
			return false;
		}
		if ( self::_checkDepth() ) {
			$variableData->extendedValue = "*DEPTH TOO GREAT*";
			return false;
		}

		$isSequential = self::_isSequential( $variable );

		$tabular = self::_isArrayTabular( $variable );
		if ( $tabular ) {

			$firstRow      = true;
			$extendedValue = '<table class="kint-report">';
			$arrayKeys     = array();


			// assure no values are unreported if an extra key appears in one of the lines
			foreach ( $variable as $row ) {
				// todo process all keys in _isArrayTabular()
				if ( !is_array( $row ) ) {
					$tabular = false;
					break;
				}
				$arrayKeys = array_unique( array_merge( $arrayKeys, array_keys( $row ) ) );

				if ( Kint::$keyFilterCallback ) {
					foreach ( $arrayKeys as $k => $key ) {
						if ( call_user_func( Kint::$keyFilterCallback, $key ) === false ) {
							unset( $arrayKeys[$k] );
						}
					}
				}
			}
		}


		if ( $tabular ) {
			$variable[self::$_marker] = true;
			foreach ( $variable as $rowIndex => &$row ) {
				if ( $rowIndex === self::$_marker ) continue;

				if ( isset( $row[self::$_marker] ) ) {
					$variableData->value = "*RECURSION*";
					return false;
				}


				$extendedValue .= '<tr>';
				$output = '<td>' . ( $isSequential ? '#' . ( $rowIndex + 1 ) : $rowIndex ) . '</td>';
				if ( $firstRow ) {
					$extendedValue .= '<th></th>';
				}

				foreach ( $arrayKeys as $key ) {
					if ( $firstRow ) {
						$extendedValue .= '<th>' . htmlspecialchars( $key ) . '</th>';
					}

					if ( !array_key_exists( $key, $row ) ) {
						$output .= '<td class="kint-empty"></td>';
						continue;
					}

					# display strings in their full length so as not to trigger rich decoration
					$maxStrLength       = kint::$maxStrLength;
					kint::$maxStrLength = false;
					$var                = kintParser::factory( $row[$key] );
					kint::$maxStrLength = $maxStrLength;

					if ( $var->value === self::$_marker ) {
						$variableData->value = '*RECURSION*';
						return false;
					} elseif ( $var->value === '*RECURSION*' ) {
						$output .= '<td class="kint-empty">' . Kint_Decorators_Concise::decorate( $var ) . '</td>';
					} else {
						$output .= '<td>' . Kint_Decorators_Concise::decorate( $var ) . '</td>';
					}
					unset( $var );
				}

				if ( $firstRow ) {
					$extendedValue .= '</tr><tr>';
					$firstRow = false;
				}

				$extendedValue .= $output . '</tr>';
			}

			$variableData->extendedValue = $extendedValue . '</table>';

		} else {
			$variable[self::$_marker] = true;
			$extendedValue            = array();

			foreach ( $variable as $key => & $val ) {
				if ( $key === self::$_marker
					|| ( Kint::$keyFilterCallback && call_user_func( Kint::$keyFilterCallback, $key, $val ) === false )
				) {
					continue;
				}


				$output = kintParser::factory( $val, $isSequential ? null : "'{$key}'" );
				if ( $output->value === self::$_marker ) {
					$variableData->value = "*RECURSION*"; // recursion occurred on a higher level, thus $this is recursion
					return false;
				}
				if ( !$isSequential ) {
					$output->operator = '=>';
				}
				$extendedValue[] = $output;
			}
			$variableData->extendedValue = $extendedValue;
		}

		if ( $globalsDetector ) {
			self::$_dealingWithGlobals = false;
		}

		unset( $variable[self::$_marker] );
	}


	private static function _parse_object( &$variable, kintVariableData $variableData )
	{

		// copy the object as an array
		$array = (array)$variable;
		$hash  = spl_object_hash( $variable );


		$variableData->type    = 'object';
		$variableData->subtype = get_class( $variable );
		$variableData->size    = count( $array );

		if ( isset( self::$_objects[$hash] ) ) {
			$variableData->value = '*RECURSION*';
			return false;
		}
		if ( self::_checkDepth() ) {
			$variableData->extendedValue = "*DEPTH TOO GREAT*";
			return false;
		}

		self::$_objects[$hash] = true;


		if ( empty( $array ) ) return;


		$extendedValue = array();
		foreach ( $array as $key => & $value ) {
			if ( Kint::$keyFilterCallback
				&& call_user_func_array( Kint::$keyFilterCallback, array( $key, $value ) ) === false
			) {
				continue;
			}

			/* casting object to array:
			 * integer properties are inaccessible;
			 * private variables have the class name prepended to the variable name;
			 * protected variables have a '*' prepended to the variable name.
			 * These prepended values have null bytes on either side.
			 * http://www.php.net/manual/en/language.types.array.php#language.types.array.casting
			 */
			if ( $key[0] === "\x00" ) {

				$access = $key[1] === "*" ? "protected" : "private";

				// Remove the access level from the variable name
				$key = substr( $key, strrpos( $key, "\x00" ) + 1 );
			} else {
				$access = "public";
			}

			$key              = self::_escape( $key );
			$output           = kintParser::factory( $value, $key );
			$output->access   = $access;
			$output->operator = '->';
			$extendedValue[]  = $output;
		}

		$variableData->extendedValue = $extendedValue;
	}


	private static function _parse_boolean( &$variable, kintVariableData $variableData )
	{
		$variableData->type  = 'bool';
		$variableData->value = $variable ? 'TRUE' : 'FALSE';
	}

	private static function _parse_double( &$variable, kintVariableData $variableData )
	{
		$variableData->type  = 'float';
		$variableData->value = $variable;
	}

	private static function _parse_integer( &$variable, kintVariableData $variableData )
	{
		$variableData->type  = 'integer';
		$variableData->value = $variable;
	}

	private static function _parse_null( &$variable, kintVariableData $variableData )
	{
		$variableData->type = 'NULL';
	}

	private static function _parse_resource( &$variable, kintVariableData $variableData )
	{
		$variableData->type    = 'resource';
		$variableData->subtype = get_resource_type( $variable );

		if ( $variableData->subtype === 'stream' && $meta = stream_get_meta_data( $variable ) ) {

			if ( isset( $meta['uri'] ) ) {
				$file = $meta['uri'];

				if ( function_exists( 'stream_is_local' ) ) {
					// Only exists on PHP >= 5.2.4
					if ( stream_is_local( $file ) ) {
						$file = Kint::shortenPath( $file );
					}
				}

				$variableData->value = $file;
			}
		}
	}

	private static function _parse_string( &$variable, kintVariableData $variableData )
	{
		$variableData->type = 'string';

		$encoding = self::_detectEncoding( $variable );
		if ( $encoding !== 'ASCII' ) {
			$variableData->subtype = $encoding;
		}

		$variableData->size = self::_strlen( $variable, $encoding );
		$strippedString     = self::_stripWhitespace( $variable );
		if ( Kint::$maxStrLength && $variableData->size > Kint::$maxStrLength ) {

			// encode and truncate
			$variableData->value         = '&quot;'
				. self::_escape( self::_substr( $strippedString, Kint::$maxStrLength, $encoding ), $encoding )
				. '&nbsp;&hellip;&quot;';
			$variableData->extendedValue = self::_escape( $variable, $encoding );

		} elseif ( $variable !== $strippedString ) { // omit no data from display

			$variableData->value         = '&quot;' . self::_escape( $variable, $encoding ) . '&quot;';
			$variableData->extendedValue = self::_escape( $variable, $encoding );
		} else {
			$variableData->value = '&quot;' . self::_escape( $variable, $encoding ) . '&quot;';
		}
	}

	private static function _parse_unknown( &$variable, kintVariableData $variableData )
	{
		$variableData->type    = "UNKNOWN";
		$variableData->subtype = gettype( $variable );
		$variableData->value   = var_export( $variable, true );
	}

}
