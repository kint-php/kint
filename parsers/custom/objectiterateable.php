<?php
class Kint_Parsers_objectIterateable extends kintParser
{
	protected function _parse( & $variable )
	{
		if ( !is_object( $variable ) ) return false;

		$reflection = new ReflectionObject( $variable );
		if ( !$reflection->isIterateable() ) return false;

		$current = 0;
		$arrayCopy = iterator_to_array( $variable );
		foreach ( $arrayCopy as $k => $var ) {
			$t             = kintParser::factory( $var );
			$t->name       = "'{$k}'";
			$t->operator   = '=>';
			$this->value[] = $t;

			if ( ++$current > 10 ) {
				break;
			}
		}

		$this->type = 'Iterator contents';
		$this->size = count( $arrayCopy );
	}
}