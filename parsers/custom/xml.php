<?php
class Kint_Parsers_Xml extends kintParser
{
	protected function _parse( & $variable )
	{
		try {
			if ( !is_string( $variable )
				|| substr( $variable, 0, 5 ) !== '<?xml'
				|| ( $xml = simplexml_load_string( $variable ) ) === null
			) return false;
		} catch ( Exception $e ) {
			return false;
		}

		$this->extendedValue = kintParser::factory( $val )->extendedValue;
		$this->type          = 'XML';
	}
}