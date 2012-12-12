<?php
class Kint_Parsers_ArrayObject extends kintParser
{
	protected function _parse( & $variable )
	{
		if ( !is_object( $variable ) ) return false;

		$reflection  = new ReflectionClass( $variable );
		$parentClass = $reflection->getParentClass();

		if ( $parentClass !== false && $parentClass->name === 'ArrayObject' ) {
			$arrayCopy = $variable->getArrayCopy();
			foreach ( $arrayCopy as $k => $var ) {
				$t             = kintParser::factory( $var );
				$t->name       = "'{$k}'";
				$t->operator   = '=>';
				$this->value[] = $t;
			}

			$this->type = 'ArrayObject contents';
			$this->size = count( $arrayCopy );
		} else {
			return false;
		}
	}
}
