<?php
class Kint_Parsers_SplObjectStorage extends kintParser
{
	protected function _parse( & $variable )
	{
		if ( !is_object( $variable ) || get_class( $variable ) !== 'SplObjectStorage' ) return false;

		/** @var $variable SplObjectStorage */

		$variable->rewind();
		while ( $variable->valid() ) {
			$current       = $variable->current();
			$this->value[] = kintParser::factory( $current );
			$variable->next();
		}

		$this->type = 'Storage contents';
		$this->size = $variable->count();
	}
}