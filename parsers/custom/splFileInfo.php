<?php
class KintParser_SplFileInfo extends kintParser
{
	static function _initialize( $options )
	{ }

	/**
	 * lets the caller know if this class can dump the current variable
	 *
	 * @param mixed $variable
	 *
	 * @return bool
	 */
	static function _fits( $variable )
	{
		return is_object( $variable ) && $variable instanceof SplFileInfo;
	}

	/**
	 * @param SplFileInfo $variable
	 * @param             $level
	 */
	protected function _parse( & $variable, $level )
	{
		$this->_type    = 'object';
		$this->_subtype = 'SplFileInfo';
		$this->_value   = call_user_func( self::$pathDisplayCallback, $variable->getRealPath() );
	}
}