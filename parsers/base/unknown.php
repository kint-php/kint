<?php
class KintParser_Unknown extends kintParser
{
	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{ }

	protected function _parse( & $variable, $level )
	{
		$this->_type    = "UNKNOWN!";
		$this->_subtype = gettype( $variable );
		$this->_value   = var_export( $variable, true );
	}
}