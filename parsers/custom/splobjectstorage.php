<?php
class Kint_Parsers_SplObjectStorage extends kintParser
{
	protected function _parse( & $variable )
	{
		if ( !is_object( $variable ) || get_class( $variable ) !== 'SplObjectStorage' ) return false;

		/** @var $variable SplObjectStorage */

		$variable->rewind();
		while ( $variable->valid() ) {
			$tmp            = kintParser::factory( $variable->current() );
			$this->_value[] = $tmp;
			$variable->next();
		}

		$this->_type = 'Storage contents';
		$this->_size = $variable->count();
	}
}