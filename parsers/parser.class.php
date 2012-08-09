<?php
/**
 * @property string       $access
 * @property string       $name
 * @property string       $operator
 * @property string       $type
 * @property string       $subtype
 * @property int          $size
 * @property string       $value inline value
 * @property array        $alternatives
 * @property kintParser[] $extendedValue  array of kintParser objects or strings; displayed collapsed, each
 *                        element from the array is a separate possible representation of the dumped var
 */
abstract class kintParser extends Kint
{
	protected $_type;
	protected $_access;
	protected $_name;
	protected $_operator;
	protected $_subtype;
	protected $_size;
	protected $_extendedValue;
	protected $_alternatives;
	protected $_value;

	/**
	 * main and usually single method a custom parser must implement
	 *
	 * @param mixed $variable
	 * @param array $options for custom parsers from config file
	 *
	 * @internal param int $level depth of currently dumped var
	 * @return mixed [!!!] false is returned if the variable is not of current type
	 */
	abstract protected function _parse( & $variable, $options );


	/**
	 * the only public entry point to return a parsed representation of a variable
	 *
	 * @static
	 *
	 * @param      $variable
	 * @param null $name
	 * @param int  $level
	 *
	 * @throws Exception
	 * @return \kintParser
	 */
	public final static function factory( & $variable, $name = null, $level = 0 )
	{
		$methodName = '_parse_' . gettype( $variable );

		/** @var $mainObject kintParser  */
		$mainObject        = new Kint_Parsers_BaseTypes;
		$mainObject->_name = $name;

		$ret = $mainObject->$methodName( $variable, $level );
		if ( $ret === false ) return $mainObject; // base type parser returning false means "stop processing further": e.g. depth too great

		// now check whether the variable can be represented in a different way
		foreach ( self::$customDataTypes as $parserClass => $options ) {
			$className = 'Kint_Parsers_' . $parserClass;

			/** @var $object kintParser  */
			$object        = new $className;
			$object->_name = $name; // the parser may overwrite the name value, so set it first

			$ret = $object->_parse( $variable, $options );
			if ( $ret === false ) continue;

			if ( isset( $ret ) && $ret instanceof self ) {
				$object = $ret; // one can return a kintParser instance instead of operating on $this
			}


			$mainObject->_alternatives[] = $object;
		}
		if ( !empty( $mainObject->_alternatives ) && isset( $mainObject->_extendedValue ) ) {
			$a = new Kint_Parsers_BaseTypes;
			$a->_value = $mainObject->_extendedValue;
			$a->_type = $mainObject->_type;
			$a->_size = $mainObject->_size;

			array_unshift( $mainObject->_alternatives, $a );
			$mainObject->_extendedValue = null;
		}

		return $mainObject;
	}

	/**
	 * for use in decorators
	 *
	 * @param $name
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __get( $name )
	{
		if ( in_array( $name, array( 'access', 'name', 'operator', 'type', 'subtype', 'size', 'value', 'extendedValue', 'alternatives' ) ) ) {
			$name = '_' . $name;
			return $this->{$name};
		} else {
			throw new Exception( 'Inaccessible property ' . $name );
		}
	}
}

