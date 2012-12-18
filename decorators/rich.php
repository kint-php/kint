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
	 * @param kintVariableData|kintVariableData[] $kintVar
	 *
	 * @return string
	 */
	public static function decorate( kintVariableData $kintVar )
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

		if ( isset( $kintVar->extendedValue ) ) {

			if ( is_array( $kintVar->extendedValue ) ) {
				foreach ( $kintVar->extendedValue as $v ) {
					$output .= self::decorate( $v );
				}
			} elseif ( is_string( $kintVar->extendedValue ) ) {
				$output .= '<pre>' . $kintVar->extendedValue . '</pre>';
			} else {
				$output .= self::decorate( $kintVar->extendedValue ); //it's kint's container
			}

		} elseif ( isset( $kintVar->alternatives ) ) {

			$output .= "<ul class=\"kint-tabs\">";

			foreach ( $kintVar->alternatives as $k => $var ) {
				$active = $k === 0 ? ' class="kint-active-tab"' : '';
				$output .= "<li{$active}>" . self::_drawHeader( $var, false ) . '</li>';
			}

			$output .= "</ul><ul>";

			foreach ( $kintVar->alternatives as $var ) {
				$output .= "<li>";

				//todo
				$var = $var->value;

				if ( !isset( $var ) ) {

				} elseif ( is_array( $var ) ) {
					foreach ( $var as $v ) {
						$output .= self::decorate( $v );
					}
				} elseif ( is_string( $var ) ) {
					$output .= '<pre>' . $var . '</pre>';
				} else {
					$value = isset( $var->value )
						? $var->value
						: $var->extendedValue;


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
			$output .= "(" . $kintVar->size . ") ";
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
		self::$_firstRun = false;

		$jsDir = KINT_DIR . 'view/' . ( self::$devel ? 'src/' : '' ); // load uncompressed sources if in devel mode

		$cssFile = ( self::$devel ? $jsDir : KINT_DIR . 'view/themes/' ) . self::$theme . '.css';

		if ( !is_readable( $cssFile ) ) {
			$cssFile = ( self::$devel ? $jsDir : KINT_DIR . 'view/themes/' ) . 'original.css';
		}

		return '<script>' . file_get_contents( $jsDir . 'kint.js' ) . '</script>'
			. '<style>' . file_get_contents( $cssFile ) . "</style>\n";
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
			? 'Called from ' . self::shortenPath( $callee['file'], $callee['line'] )
			: '';


		return $calleeInfo || $callingFunction
			? "<footer>{$calleeInfo}{$callingFunction}</footer></div>"
			: "</div>";
	}

}
