<?php
class KintParser_Integer extends kintParser
{
	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{ }

	protected function _parse( & $variable, $level )
	{
		$this->_type  = 'integer';
		$this->_value = $variable;
	}
}