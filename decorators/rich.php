<?php
class kintRichDecorator extends Kint
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
	public function decorate( kintParser $kintVar )
	{
		$output = '<dl>';
		if ( is_array( $kintVar->value ) || $kintVar->extendedValue !== null ) {
			$output .= "<dt class=\"kint-parent\"><div class=\"kint-plus _kint-collapse\"></div>";
		} else {
			$output .= "<dt>";
		}


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

		if ( $kintVar->value !== null || $kintVar->extendedValue !== null ) {


			if ( $kintVar->extendedValue ) {
				$output .= '<dd><pre>' . $kintVar->extendedValue . '</pre></dd>';
			}

			if ( is_array( $kintVar->value ) ) {
				$output .= '</dt><dd>';
				foreach ( $kintVar->value as $var ) {
					$output .= self::decorate( $var );
				}
				$output .= '</dd>';
			} else {
				$output .= $kintVar->value . '</dt>';
			}
		}

		$output .= '</dl>';

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

		$ret = self::$charset
			? '<head><meta charset="' . self::$charset . '"></head>' // add charset meta tag if requested
			: '';


		$dir = KINT_DIR . 'view/' . ( self::$devel ? 'src/' : '' ); // load uncompressed sources if in devel mode
		$ret .= '<script>' . file_get_contents( $dir . 'kint.js' ) . '</script>'
			. '<style>' . file_get_contents( $dir . 'kint.css' ) . '</style>';

		return $ret;
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