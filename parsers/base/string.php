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

		/*
		// check whether string is JSON
		$isJson = json_decode( $var );
		if ( $isJson !== null ) {
		  // it is!
		}

		// check whether string is XML
		$oldValue = libxml_use_internal_errors( true );
		try {
		   $simpleXml = new SimpleXMLElement( $var );
		   libxml_use_internal_errors( $oldValue );

		   $var = $simpleXml->asXML();
		} catch ( Exception $e ) {
		   libxml_clear_errors();
		}
		libxml_use_internal_errors( $oldValue );
		// END check whether string is XML
		*/

		$this->_size    = strlen( $variable );
		$strippedString = self::_stripWhitespace( $variable );
		if ( $this->_size > self::$maxStrLength ) {

			// encode and truncate
			$this->_value         = self::_escape( substr( $strippedString, 0, self::$maxStrLength ) ) . '&nbsp;&hellip;';
			$this->_extendedValue = self::_escape( $variable );

		} elseif ( $variable !== $strippedString ) { // omit no data from display

			$this->_value         = self::_escape( $strippedString );
			$this->_extendedValue = self::_escape( $variable );
		} else {
			$this->_value = self::_escape( $variable );
		}
	}
}