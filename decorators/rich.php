<?php
class Kint_Decorators_Rich extends Kint
{
	private static $_firstRun = true;
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
	public static function decorate( kintParser $kintVar )
	{
		$output = '<dl>';

		if ( $kintVar->extendedValue !== null ) {
			$output .= "<dt class=\"kint-parent\"><div class=\"kint-plus _kint-collapse\"></div>";
		} else {
			$output .= "<dt>";
		}

		$output .= self::_drawHeader( $kintVar );

		$output .= $kintVar->value . '</dt>';

		if ( ( $kintVar->extendedValue !== null ) ) { // isset does not work for __get

			$output .= '<dd>';


			$moreThanOneRepresentation = count( $kintVar->extendedValue ) > 1;
			if ( $moreThanOneRepresentation ) {
				$output .= "<ul>";

				foreach ( $kintVar->extendedValue as $k => $var ) {


					$output .= "<li class=\"kint-tabheader-{$k}\">";
					$output .= $k
						? self::_drawHeader( $var )
						: 'main';
					$output .= '</li>';
				}

				$output .= "</ul>";
			}

			if ( $moreThanOneRepresentation ) {
				$output .= "<ul>";
			}
			foreach ( $kintVar->extendedValue as $k => $var ) {
				if ( $moreThanOneRepresentation ) {
					$output .= "<li class=\"kint-tab-{$k}\">";
				}
				if ( is_array( $var ) ) {
					foreach ( $var as $v ) {
						$output .= self::decorate( $v );
					}
				} elseif ( is_string( $var ) ) {
					$output .= $var;
				} elseif ( is_string( $var->value ) ) {
					$output .= $var->value;
				} else {
					$output .= self::decorate( $var->value );
				}

				if ( $moreThanOneRepresentation ) {
					$output .= "</li>";
				}
			}

			if ( $moreThanOneRepresentation ) {
				$output .= "</ul>";
			}
			$output .= '</dd>';
		}

		$output .= '</dl>';

		return $output;
	}

	private static function _drawHeader( $kintVar )
	{
		$output = '';
		if ( $kintVar->access !== null ) {
			$output .= "<var>" . $kintVar->access . "</var> ";
		}

		if ( $kintVar->name !== null ) {
			$output .= "<dfn>" . $kintVar->name . "</dfn> ";
		}

		if ( $kintVar->operator !== null ) {
			$output .= "" . $kintVar->operator . " ";
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
		if ( self::$colorCodeLoops && isset( $callee['file'] ) ) {
			$uid = crc32( $callee['file'] . $callee['line'] );

			if ( isset( self::$_usedColors[$uid] ) ) {
				$class = self::$_usedColors[$uid];
			} else {
				$class                   = sizeof( self::$_usedColors );
				self::$_usedColors[$uid] = $class;
			}

			$class = " kint-c-{$class}";
		} else {
			$class = '';
		}

		return "<div class=\"kint{$class}\">";
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
			? 'Called from ' . call_user_func( self::$pathDisplayCallback, $callee['file'], $callee['line'] )
			: '';


		return $calleeInfo || $callingFunction
			? "<footer>{$calleeInfo}{$callingFunction}</footer></div>"
			: "</div>";
	}

}