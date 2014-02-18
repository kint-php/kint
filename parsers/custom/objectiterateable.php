<?php

class Kint_Parsers_objectIterateable extends kintParser
{
	protected function _parse( & $variable )
	{
		if ( !is_object( $variable ) || !$variable instanceof Traversable ) return false;


		$arrayCopy   = iterator_to_array( $variable, true );
		$this->value = kintParser::factory( $arrayCopy )->extendedValue;
		$this->type  = 'Iterator contents';
		$this->size  = count( $arrayCopy );
	}
}