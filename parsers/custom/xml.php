<?php
class Kint_Parsers_Xml extends kintParser
{
	protected function _parse( & $variable, $options )
	{
		if ( !is_string( $variable )
			|| substr( $variable, 0, 5 ) !== '<?xml'
			|| ( $xml = simplexml_load_string( $variable ) ) === null
		) return false;

		$this->_value = kintParser::factory( $xml );
		$this->_type  = 'XML';
	}
}