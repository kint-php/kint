<?php
class KintParser_Json extends kintParser
{
	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{
		return !is_numeric( $variable ) && is_string( $variable ) && json_decode( $variable ) !== null;
	}


	protected function _parse( & $variable, $level )
	{
		$json         = json_decode( $variable );
		$ret          = kintParser::factory( $json );
		$ret->type    = 'string';
		$ret->subtype = 'JSON';
		$ret->name    = $this->name;

		return $ret;
	}
}