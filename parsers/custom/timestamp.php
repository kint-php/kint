<?php
class Kint_Parsers_Timestamp extends kintParser
{
	static function _fits( $variable )
	{
		if ( !is_string( $variable ) && !is_int( $variable ) ) return false;

		$len = strlen( $variable );
		return ( $len === 9 || $len === 10 ) // a little naive
			&& ( (string)(int)$variable == $variable )
			&& ( $variable <= PHP_INT_MAX )
			&& ( $variable > 0 );
	}


	protected function _parse( & $variable, $options )
	{
		if ( !self::_fits( $variable ) ) return false;

		$this->_type    = 'integer';
		$this->_subtype = 'timestamp';
		$this->_value   = date( 'Y-m-d H:i:s', $variable );
	}
}