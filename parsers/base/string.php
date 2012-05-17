<?php
class KintParser_String extends kintParser
{
	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{ }

	// todo: make ascii control characters show up in some way; take more care of utf8
	protected function _parse( & $variable, $level )
	{
		$this->_type = 'string';

		if ( is_callable( $variable ) ) {
			$this->_subtype = '[callable]';
		} else {
			$subtype = mb_detect_encoding( $variable );
			if ( $subtype !== 'ASCII' ) {

				$this->_subtype = $subtype;
			}
		}

		$this->_size    = strlen( $variable );
		$strippedString = self::_stripWhitespace( $variable );
		if ( $this->_size > self::$maxStrLength ) {

			// encode and truncate
			$this->_value         = '&quot;' . self::_escape( substr( $strippedString, 0, self::$maxStrLength ) ) . '&nbsp;&hellip;&quot;';
			$this->_extendedValue = self::_escape( $variable );

		} elseif ( $variable !== $strippedString ) { // omit no data from display

			$this->_value         = '&quot;' . self::_escape( $strippedString ) . '&quot;';
			$this->_extendedValue = self::_escape( $variable );
		} else {
			$this->_value = '&quot;' . self::_escape( $variable ) . '&quot;';
		}
	}
}