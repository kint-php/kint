<?php
class Kint_Parsers_Microtime extends kintParser
{
	private static $_times = array();

	protected function _parse( & $variable )
	{
		if ( !is_string( $variable ) || !preg_match( '[0\.[0-9]{8} [0-9]{10}]', $variable ) ) {
			return false;
		}

		list( $usec, $sec ) = explode( " ", microtime() );

		$time = ( (float)$usec + (float)$sec );

		$this->value = @date( 'Y-m-d H:i:s', $sec ) . '.' . substr( $usec, 2, -2 );

		$numberOfCalls = count( self::$_times );
		if ( $numberOfCalls > 0 ) { # meh, faster than count($times) > 1
			$this->value .= "\n<strong>SINCE LAST CALL:</strong> " . ( $time - end( self::$_times ) ) . 's.';
			if ( $numberOfCalls > 1 ) {
				$this->value .= "\n<strong>SINCE START:</strong> " . ( $time - self::$_times[0] ) . 's.';
			}
		}

		self::$_times[] = $time;

		$this->type = 'Microtime';
	}
}