<?php
interface kintParserInterface
{
	/**
	 * called on startup
	 *
	 * @static
	 * @abstract
	 *
	 * @param mixed $options passed configuration values from config.php/config.default.php
	 *
	 * @return void
	 */
	static function _initialize( $options );

	/**
	 * lets the caller know if this class can dump the current variable
	 *
	 * @static
	 * @abstract
	 *
	 * @param mixed $variable
	 *
	 * @return bool
	 */
	static function _fits( $variable );
}

/**
 * @property string $access
 * @property string $name
 * @property string $operator
 * @property string $type
 * @property string $subtype
 * @property string $size
 * @property mixed  $value
 * @property string $extendedValue
 */

abstract class kintParser extends Kint implements kintParserInterface
{
	protected $_access;
	protected $_name;
	protected $_operator;
	protected $_type;
	protected $_subtype;
	protected $_size;
	protected $_extendedValue;
	protected $_value; // value is either scalar or array of to be processed variables

	private static $_initialized = false;
	private static $_customParsers = array();


	abstract protected function _parse( & $variable, $level );

	public final static function factory( & $variable, $name = null, $level = 0 )
	{
		self::_init();


		foreach ( self::$_customParsers as $parserClass ) {
			if ( call_user_func( array( $parserClass, '_fits' ), $variable ) ) {
				$className = $parserClass;
				break;
			}
		}

		// resort to base types if no custom parser found
		if ( !isset( $className ) ) {
			$type      = gettype( $variable );
			$filename  = dirname( __FILE__ ) . '/base/' . strtolower( $type ) . '.php';
			$className = 'KintParser_' . ucfirst( $type );

			if ( !class_exists( $className, false ) ) {
				if ( file_exists( $filename ) ) {
					require $filename;
				} else {
					require dirname( __FILE__ ) . '/base/unknown.php';
					$className = 'KintParser_Unknown';
				}
			}
		}


		/** @var $object kintParser  */
		$object        = new $className;
		$object->_name = $name;
		$o             = $object->_parse( $variable, $level ); // one can return a kintParser instance instead of operating on $this
		if ( isset( $o ) && $o instanceof self ) {
			$object = $o;
		}

		return $object;
	}


	public static function _init()
	{
		if ( self::$_initialized ) return;
		self::$_initialized = true;

		foreach ( self::$customDataTypes as $name => $options ) {
			$filename  = dirname( __FILE__ ) . '/custom/' . $name . '.php';
			$className = 'KintParser_' . $name;

			if ( !class_exists( $className, false ) ) {
				if ( file_exists( $filename ) ) {
					require $filename;
					call_user_func( array( $className, '_initialize' ), $options );
					self::$_customParsers[] = $className;
				}
			}
		}
	}

	public function __get( $name )
	{
		if ( in_array( $name, array( 'access', 'name', 'operator', 'type', 'subtype', 'size', 'value', 'extendedValue', ) ) ) {
			$name = '_' . $name;
			return $this->{$name};
		} else {
			throw new Exception( 'Inaccessible property ' . $name );
		}
	}


	/* ******************
	 * HELPER METHODS
	 */


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
		return self::$hideSequentialKeys
			? array_keys( $array ) === range( 0, count( $array ) - 1 )
			: false;
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
		$search = array(
			'#[ \t]+[\r\n]#' => "", // leading whitespace after line end
			'#[\n\r]+#'      => "\n", // multiple newlines
			'# {2,}#'        => " ", // multiple spaces
			'#\t{2,}#'       => "\t", // multiple tabs
			'#\t | \t#'      => "\t", // tabs and spaces together
		);
		return preg_replace( array_keys( $search ), $search, trim( $string ) );
	}


}