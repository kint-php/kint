<?php
class Kint_Parsers_TabularArray extends kintParser
{
	private static $_enabledStatus = null;

	protected function _parse( & $variable, $options )
	{
		if ( !is_array( $variable ) ) return false;

		if ( self::$_enabledStatus !== 'off' ) {
			foreach ( $variable as $row ) {
				if ( is_array( $row ) && count( $row ) > 1 ) {
					if ( isset( $keys ) ) {
						if ( $keys === array_keys( $row ) ) {
							continue;
						}
					} else {

						if ( self::$_enabledStatus !== 'on' ) {
							foreach ( $row as $col ) {
								if ( !is_scalar( $col ) && $col !== null ) {
									return false;
								}
							}
						}

						$keys = array_keys( $row );
					}
				} else {
					return false;
				}
			}
		}



		$this->_type = 'tabular array';
		$this->_size = count( $variable );

		$firstRow  = true;
		$ret       = '<table class="kint-report">';
		$isIndexed = self::_isSequential( $variable );
		$arrayKeys = array();

		// assure no values are unreported if an extra key appears in one of the lines
		foreach ( $variable as $row ) {
			$arrayKeys = array_unique( array_merge( $arrayKeys, array_keys( $row ) ) );
		}

		foreach ( $variable as $rowIndex => $row ) {
			$ret .= '<tr>';
			$output = '<td>' . ( $isIndexed ? '#' . ( $rowIndex + 1 ) : $rowIndex ) . '</td>';


			if ( $firstRow ) {
				$ret .= '<th></th>';
			}


			foreach ( $arrayKeys as $key ) {
				if ( $firstRow ) {
					$ret .= '<th>' . htmlspecialchars( $key ) . '</th>';
				}

				if ( !isset( $row[$key] ) ) {
					$output .= '<td class="kint-empty"></td>';
					continue;
				} else {
					$value = $row[$key];
				}
				$str = Kint_Decorators_Rich::decorate(
					kintParser::factory( $value )
				);

				$output .= '<td>' . $str . '</td>';
			}

			if ( $firstRow ) {
				$ret .= '</tr>';
				$firstRow = false;
			}

			$ret .= $output . '</tr>';
		}

		$this->_value = $ret . '</table>';

	}


}