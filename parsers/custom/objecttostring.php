<?php
class Kint_Parsers_ObjectToString extends kintParser
{
    
	protected function _parse( & $variable )
	{
		if ( !is_object( $variable ) || ! Kint::$displayToString || ! method_exists($variable, '__toString') ) 
		    return false;

		$string = $variable->__toString();
	    $this->value = self::_escape($string);
	    $this->type = '__toString';
	    $this->size = self::_strlen($string);
	}
}