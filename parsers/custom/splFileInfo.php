<?php
class Kint_Parsers_SplFileInfo extends kintParser
{
	/**
	 * @param SplFileInfo $variable
	 * @param array       $options
	 *
	 * @return mixed|void
	 */
	protected function _parse( & $variable, $options )
	{
		if ( !is_object( $variable ) || !( $variable instanceof SplFileInfo ) ) return false;

		$this->_type    = 'object';
		$this->_subtype = 'SplFileInfo';
		$this->_value   = self::_debugPath( $variable->getRealPath() );
	}
}