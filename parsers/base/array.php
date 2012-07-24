<?php
class KintParser_Array extends kintParser
{
	private static $_marker = null;


	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{ }

	protected function _parse( & $variable, $level )
	{
		isset( self::$_marker ) or self::$_marker = uniqid( "\x00" );

		$this->_type = 'array';
		$this->_size = count( $variable );


		if ( $this->_size === 0 ) {
			return;
		}
		if ( isset( $variable[self::$_marker] ) ) {
			// todo mayhaps show from which level
			$this->_value = self::$_marker;
			return;
		}
		if ( self::$maxLevels !== 0 && $level > self::$maxLevels ) {
			$this->_value = "*DEPTH TOO GREAT*";
			return;
		}


		$isSequential             = self::_isSequential( $variable );
		$variable[self::$_marker] = TRUE;
		foreach ( $variable as $key => & $val ) {
			if ( $key === self::$_marker ) continue;

			if ( self::$keyFilterCallback
				&& call_user_func_array( self::$keyFilterCallback, array( $key, $val ) ) === false
			) {
				continue;
			}


			$output = kintParser::factory( $val, $isSequential ? null : "'{$key}'", $level + 1 );
			if ( $output->_value === self::$_marker ) {
				$this->_extendedValue   = "*RECURSION*";
				$this->_value           = null;
				break;
			}
			if ( !$isSequential ) {
				$output->_operator = '=>';
			}
			$this->_value[] = $output;
		}

		unset( $variable[self::$_marker] );
	}

}