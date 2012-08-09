<?php
class Kint_Decorators_Rich extends Kint
{
	// make calls to Kint::dump() from different places in source coloured differently.
	private static $_usedColors = array();

	/**
	 * output:
	 *
	 * [access *] [name] type [operator *] [subtype] [size] [value]
	 *
	 * @param kintParser $kintVar
	 *
	 * @return string
	 */
	public static function decorate( $kintVar )
	{
		$output = '<dl>';

		$extendedPresent = $kintVar->extendedValue !== null || $kintVar->alternatives !== null;
		if ( $extendedPresent ) {
			$output .= "<dt class=\"kint-parent\"><span class=\"kint-plus _kint-collapse\"></span>";
		} else {
			$output .= "<dt>";
		}

		$output .= self::_drawHeader( $kintVar );

		$output .= $kintVar->value . '</dt>';
		if ( $extendedPresent ) {
			$output .= '<dd>';
		}

		if ( ( $kintVar->extendedValue !== null ) ) {

			if ( is_array( $kintVar->extendedValue ) ) {
				foreach ( $kintVar->extendedValue as $v ) {
					$output .= self::decorate( $v );
				}
			} elseif ( is_string( $kintVar->extendedValue ) ) {
				$output .= '<pre>' . $kintVar->extendedValue . '</pre>';
			} else {
				$output .= self::decorate( $kintVar->extendedValue ); //it's kint's container
			}

		} elseif ( $kintVar->alternatives !== null ) { // isset does not work for __get

			$output .= "<ul class=\"kint-tabs\">";

			foreach ( $kintVar->alternatives as $k => $var ) {

				if ( $k == 0 ) {
					$output .= "<li class=\"kint-active-tab\">";
					$output .= self::_drawHeader( $var, false );
				} else {
					$output .= "<li>";
					$output .= self::_drawHeader( $var, false );
				}
				$output .= '</li>';
			}

			$output .= "</ul>";


			$output .= "<ul>";

			foreach ( $kintVar->alternatives as $var ) {

				$output .= "<li>";

				$var = $var->value;

				if ( is_array( $var ) ) {
					foreach ( $var as $v ) {
						$output .= self::decorate( $v );
					}
				} elseif ( is_string( $var ) ) {
					$output .= '<pre>' . $var . '</pre>';
				} else {

					$value = $var->value;
					if ( !isset( $value ) ) {
						$value = $var->extendedValue;
					}


					if ( !isset( $value ) ) {

					} elseif ( is_array( $value ) ) {
						foreach ( $value as $v ) {
							$output .= self::decorate( $v );
						}
					} elseif ( is_string( $value ) ) {
						$output .= '<pre>' . $value . '</pre>';
					} else {
						$output .= self::decorate( $var ); //it's kint's container
					}
				}
				$output .= "</li>";
			}

			$output .= "</ul>";
		}
		if ( $extendedPresent ) {
			$output .= '</dd>';
		}

		$output .= '</dl>';

		return $output;
	}

	private static function _drawHeader( $kintVar, $verbose = true )
	{
		$output = '';
		if ( $verbose ) {
			if ( $kintVar->access !== null ) {
				$output .= "<var>" . $kintVar->access . "</var> ";
			}

			if ( $kintVar->name !== null ) {
				$output .= "<dfn>" . $kintVar->name . "</dfn> ";
			}

			if ( $kintVar->operator !== null ) {
				$output .= "" . $kintVar->operator . " ";
			}
		}

		if ( $kintVar->type !== null ) {
			$output .= "<var>" . $kintVar->type;
			if ( $kintVar->subtype !== null ) {
				$output .= " " . $kintVar->subtype;
			}
			$output .= "</var> ";
		}


		if ( $kintVar->size !== null ) {
			$output .= "<span>(" . $kintVar->size . ")</span> ";
		}

		return $output;
	}


	/**
	 * produces css and js required for display. May be called multiple times, will only produce output once per
	 * pageload or until `-` or `@` modifier is used
	 *
	 * @return string
	 */
	protected function _css()
	{
		if ( !self::$_firstRun ) return '';
		self::$_firstRun = FALSE;

		$dir = KINT_DIR . 'view/' . ( self::$devel ? 'src/' : '' ); // load uncompressed sources if in devel mode

		return '<script>' . file_get_contents( $dir . 'kint.js' ) . '</script>'
			. '<style>' . file_get_contents( $dir . 'kint.css' ) . '</style>';
	}


	/**
	 * called for each dump, opens the html tag
	 *
	 * @param array $callee caller information taken from debug backtrace
	 *
	 * @return string
	 */
	protected static function _wrapStart( $callee )
	{
		// colors looping outputs the same (i.e. if same line in code dumps variables multiple time,
		// we assume it's in a loop)

		$uid = isset( $callee['file'] ) ? crc32( $callee['file'] . $callee['line'] ) : 'no-file';

		if ( isset( self::$_usedColors[$uid] ) ) {
			$class = self::$_usedColors[$uid];
		} else {
			$class                   = sizeof( self::$_usedColors );
			self::$_usedColors[$uid] = $class;
		}

		$class = "kint_{$class}";


		return array( "<div class=\"kint {$class}\">", $class );
	}


	/**
	 * closes Kint::_wrapStart() started html tags and displays callee information
	 *
	 * @param array $callee caller information taken from debug backtrace
	 * @param array $prevCaller previous caller information taken from debug backtrace
	 *
	 * @return string
	 */
	protected static function _wrapEnd( $callee, $prevCaller )
	{
		if ( !Kint::$displayCalledFrom ) {
			return '</div>';
		}

		$callingFunction = '';
		if ( isset( $prevCaller['class'] ) ) {
			$callingFunction = $prevCaller['class'];
		}
		if ( isset( $prevCaller['type'] ) ) {
			$callingFunction .= $prevCaller['type'];
		}
		if ( isset( $prevCaller['function'] ) && !in_array( $prevCaller['function'], Kint::$_statements ) ) {
			$callingFunction .= $prevCaller['function'] . '()';
		}
		$callingFunction and $callingFunction = " in ({$callingFunction})";


		$calleeInfo = isset( $callee['file'] )
			? 'Called from ' . self::_debugPath( $callee['file'], $callee['line'] )
			: '';


		return $calleeInfo || $callingFunction
			? "<footer>{$calleeInfo}{$callingFunction}</footer></div>"
			: "</div>";
	}

}