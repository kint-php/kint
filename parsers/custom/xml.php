<?php
class KintParser_Xml extends kintParser
{
	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{
		return is_string( $variable )
			&& substr( $variable, 0, 5 ) === '<?xml'
			&& simplexml_load_string( $variable ) !== null; // todo optimize
	}


	protected function _parse( & $variable, $level )
	{
		$xml          = simplexml_load_string( $variable );
		$ret          = kintParser::factory( $xml );
		$ret->type    = 'string';
		$ret->subtype = 'XML';
		$ret->name    = $this->name;

		return $ret;
	}
}