<?php
class Kint_Parsers_SplFileInfo extends kintParser
{
	protected function _parse( & $variable )
	{
		if ( !is_object( $variable ) || !( $variable instanceof SplFileInfo ) ) return false;

		$this->_type    = 'object';
		$this->_subtype = 'SplFileInfo';
		$this->_value   = self::_debugPath( $variable->getRealPath() );
	}
}