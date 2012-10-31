<?php
class Kint_Parsers_Timestamp extends kintParser
{
	private static function _fits( $variable )
	{
		if ( !is_string( $variable ) && !is_int( $variable ) ) return false;

		$len = strlen( (int)$variable );
		return ( $len === 9 || $len === 10 ) // a little naive
			&& ( (string)(int)$variable == $variable );
	}


	protected function _parse( & $variable )
	{
		if ( !self::_fits( $variable ) ) return false;

		$this->type = 'timestamp';
		# avoid dreaded "Timezone must be set" error
		$this->value = @date( 'Y-m-d H:i:s', $variable );
	}
}