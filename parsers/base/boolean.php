<?php
class KintParser_Boolean extends kintParser
{
	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{ }

	protected function _parse( & $variable, $level )
	{
		$this->_type  = 'bool';
		$this->_value = $variable ? 'TRUE' : 'FALSE';
	}
}