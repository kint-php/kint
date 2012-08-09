<?php
class Kint_Parsers_ArrayObject extends kintParser
{
	protected function _parse( & $variable, $options )
	{
		if ( !is_object( $variable ) ) return false;

		$reflection  = new ReflectionClass( $variable );
		$parentClass = $reflection->getParentClass();

		if ( $parentClass !== false && $parentClass->name === 'ArrayObject' ) {
			$arrayCopy = $variable->getArrayCopy();
			foreach ( $arrayCopy as $k => $var ) {
				$t              = kintParser::factory( $var );
				$t->_name       = $k;
				$this->_value[] = $t;
			}

			$this->_type = 'ArrayObject';
			$this->_size = count( $arrayCopy );
		} else {
			return false;
		}
	}
}