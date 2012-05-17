<?php
class KintParser_Object extends kintParser
{
	private static $_objects;

	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{ }


	protected function _parse( & $variable, $level )
	{
		// copy the object as an array
		$array = (array)$variable;
		$hash  = spl_object_hash( $variable );


		$this->_type    = 'object';
		$this->_subtype = get_class( $variable );
		$this->_size    = count( $array );

		if ( isset( self::$_objects[$hash] ) ) {
			$this->_extendedValue = '*RECURSION*';
			return;
		}
		if ( self::$maxLevels !== 0 && $level > self::$maxLevels ) {
			$this->_extendedValue = "*DEPTH TOO GREAT*";
			return;
		}

		self::$_objects[$hash] = TRUE;

		$reflection = new ReflectionClass( $variable );
		// first show static values
		foreach ( $reflection->getProperties( ReflectionProperty::IS_STATIC ) as $property ) {
			if ( $property->isPrivate() ) {
				$property->setAccessible( true );
				$access = "private";
			} elseif ( $property->isProtected() ) {
				$property->setAccessible( true );
				$access = "protected";
			} else {
				$access = 'public';
			}
			$access .= " static";

			$_      = $property->getValue();
			$output = kintParser::factory( $_, '$' . $property->getName(), $level + 1 );

			$output->_access   = $access;
			$output->_operator = '::';
			$this->_value[]    = $output;
		}

		if ( self::$showClassConstants ) foreach ( $reflection->getConstants() as $constant => $val ) {
			$output = kintParser::factory( $val, $constant, $level + 1 );

			$output->_access   = 'constant';
			$output->_operator = '::';
			$this->_value[]    = $output;
		}

		if ( empty( $array ) ) {
			return;
		}

		foreach ( $array as $key => & $value ) {
			if ( self::$keyFilterCallback
				&& call_user_func_array( self::$keyFilterCallback, array( $key, $value ) ) === false
			) {
				continue;
			}

			/* casting object to array:
			 * integer properties are unaccessible;
			 * private variables have the class name prepended to the variable name;
			 * protected variables have a '*' prepended to the variable name.
			 * These prepended values have null bytes on either side.
			 * http://www.php.net/manual/en/language.types.array.php#language.types.array.casting
			 */
			if ( $key[0] === "\x00" ) {

				$access = $key[1] === "*" ? "protected" : "private";

				// Remove the access level from the variable name
				$key = substr( $key, strrpos( $key, "\x00" ) + 1 );
			}
			else {
				$access = "public";
			}

			$output            = kintParser::factory( $value, $key, $level + 1 );
			$output->_access   = $access;
			$output->_operator = '->';
			$this->_value[]    = $output;
		}
		unset( self::$_objects[$hash] );
	}
}