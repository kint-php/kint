<?php
class KintParser_Resource extends kintParser
{
	static function _initialize( $options )
	{ }

	static function _fits( $variable )
	{ }

	protected function _parse( & $variable, $level )
	{
		$this->_type    = 'resource';
		$this->_subtype = get_resource_type( $variable );

		if ( $this->_subtype === 'stream' && $meta = stream_get_meta_data( $variable ) ) {

			if ( isset( $meta['uri'] ) ) {
				$file = $meta['uri'];

				if ( function_exists( 'stream_is_local' ) ) {
					// Only exists on PHP >= 5.2.4
					if ( stream_is_local( $file ) ) {
						$file = self::_debugPath( $file );
					}
				}

				$this->_value = $file;
			}
		}
	}
}