<?php

class Kint_Parsers_SplFileInfo extends kintParser
{
	protected function _parse( & $variable )
	{
		if ( !KINT_PHP53 || !is_object( $variable ) || !$variable instanceof SplFileInfo ) return false;

		$this->type  = 'SplFileInfo';
		$this->value = $variable->getRealPath();
	}
}