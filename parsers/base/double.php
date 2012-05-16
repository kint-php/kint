<?php
class KintParser_Double extends kintParser
{
	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{ }

	protected function _parse( & $variable, $level )
	{
		$this->_type  = 'float';
		$this->_value = $variable;
	}
}