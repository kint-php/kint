<?php
class Kint_Parsers_ArrayObject extends kintParser
{
	protected function _parse( & $variable, $options )
	{
		if ( !is_object( $variable ) ) return false;

		$reflection  = new ReflectionClass( $variable );
		$parentClass = $reflection->getParentClass();

		if ( $parentClass !== false && $parentClass->name === 'ArrayObject' ) {
			$this->_value = kintParser::factory( $variable->getArrayCopy() );
			$this->_type  = 'ArrayObject';
		} else {
			return false;
		}
	}
}