<?php
class Kint_Parsers_Microtime extends kintParser
{
	private static $_times = array();
	private static $_laps = array();

	protected function _parse( & $variable )
	{
		if ( !is_string( $variable ) || !preg_match( '[0\.[0-9]{8} [0-9]{10}]', $variable ) ) {
			return false;
		}

		list( $usec, $sec ) = explode( " ", microtime() );

		$time = (float)$usec + (float)$sec;
		$size = memory_get_usage( true );

		$this->value = @date( 'Y-m-d H:i:s', $sec ) . '.' . substr( $usec, 2, 4 );

		$numberOfCalls = count( self::$_times );
		if ( $numberOfCalls > 0 ) { # meh, faster than count($times) > 1
			$lap           = $time - end( self::$_times );
			self::$_laps[] = $lap;

			$this->value .= "\n<strong>SINCE LAST CALL:</strong> " . round( $lap, 4 ) . 's.';
			if ( $numberOfCalls > 1 ) {
				$this->value .= "\n<strong>SINCE START:</strong> " . round( $time - self::$_times[0], 4 ) . 's.';
				$this->value .= "\n<strong>AVERAGE DURATION:</strong> "
					. round( array_sum( self::$_laps ) / $numberOfCalls, 4 ) . 's.';
			}
		}

		$unit = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$this->value .= "\n<strong>MEMORY USAGE:</strong> " . $size . " bytes ("
			. round( $size / pow( 1024, ( $i = floor( log( $size, 1024 ) ) ) ), 3 ) . ' ' . $unit[$i] . ")";

		self::$_times[] = $time;
		$this->type     = 'Stats';
	}

	/*
	function test() {
		$x = '';
		d( microtime() );
		for ( $i = 0; $i < 10; $i++ ) {
			$x .= str_repeat( 'x', mt_rand( 128 * 1024, 5 * 1024 * 1024 ) );
			usleep( mt_rand( 0, 1000 * 1000 ) );
			d( microtime() );
		}
		unset( $x );
		dd( microtime() );
	}
	 */
}