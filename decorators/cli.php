<?php
class Kint_Decorators_Cli extends Kint
{
	private static $_enableColors;

	public static function decorate( kintVariableData $kintVar, $level = 0 )
	{
		self::_init();

		$output = '';
		if ( $level === 0 ) {
			$name = $kintVar->name ? $kintVar->name : 'literal';
			if ( self::$_enableColors ) {
				$output .= "\033[0;32m#-----------------{$name}#-----------------#\033[0m\n";
			} else {
				$output .= "#-----------------{$name}#-----------------#\n";
			}
			$kintVar->name = null;
		}

		if ( $level === 0 ) {
			if ( self::$_enableColors ) {
				$output .= "\033[0;32m#-------------{$kintVar->name}------------#\033[0m\n";
			} else {
				$output .= "#-------------{$kintVar->name}------------#a\n";
			}
			$kintVar->name = null;
		}

		$space = str_repeat( $s = '    ', $level );
		$output .= $space . self::_drawHeader( $kintVar );


		if ( $kintVar->extendedValue !== null ) {
			$output .= " [\n";

			if ( is_array( $kintVar->extendedValue ) ) {
				foreach ( $kintVar->extendedValue as $v ) {
					$output .= self::decorate( $v, $level + 1 );
				}
			} elseif ( is_string( $kintVar->extendedValue ) ) {
				$output .= $space . $s . $kintVar->extendedValue . "\n"; # depth too great or similar
			} else {
				$output .= self::decorate( $kintVar->extendedValue, $level + 1 ); //it's kint's container
			}
			$output .= $space . "]\n";
		} else {
			$output .= "\n";
		}

		return $output;
	}

	public static function decorateTrace( $traceData )
	{
		$output = '';
		foreach ( $traceData as $i => $step ) {
			$output .= $i + 1 . ': ';

			if ( isset( $step['file'] ) ) {
				$output .= self::_buildCalleeString( $step );
			} else {
				$output .= 'PHP internal call';
			}

			$output .= ' ' . $step['function'];

			if ( isset( $step['args'] ) ) {
				$output .= '(' . implode( ', ', array_keys( $step['args'] ) ) . ')';
			}
			$output .= "\n";


			if ( !empty( $step['object'] ) ) {
				$calleeDump = kintParser::factory( $step['object'] );
				$output .= "## Callee object ##\n";
				$output .= self::decorate( $calleeDump, 1 );
			}
			if ( !empty( $step['args'] ) ) {
				$output .= "## Arguments ##\n";
				foreach ( $step['args'] as $k => $arg ) {
					kintParser::reset();
					$output .= self::decorate( kintParser::factory( $arg, $k ), 1 );
				}
			}
		}

		return $output;
	}


	/**
	 * called for each dump, opens the html tag
	 *
	 * @param array $callee caller information taken from debug backtrace
	 *
	 * @return string
	 */
	public static function wrapStart( $callee )
	{
		return "";
	}


	/**
	 * closes Kint::_wrapStart() started html tags and displays callee information
	 *
	 * @param array $callee caller information taken from debug backtrace
	 * @param array $miniTrace full path to kint call
	 * @param array $prevCaller previous caller information taken from debug backtrace
	 *
	 * @return string
	 */
	public static function wrapEnd( $callee, $miniTrace, $prevCaller )
	{
		if ( !Kint::$displayCalledFrom ) {
			return "\n";
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
		$callingFunction and $callingFunction = " in {$callingFunction}";

		$calleeInfo = null;
		if ( isset( $callee['file'] ) ) {
			$calleeInfo = self::_buildCalleeString( $callee );
		}


		return $calleeInfo || $callingFunction
			? "Called from {$calleeInfo}{$callingFunction}\n"
			: "\n";
	}


	private static function _drawHeader( kintVariableData $kintVar )
	{
		$output = '';

		if ( $kintVar->access ) {
			$output .= ' ' . $kintVar->access;
		}

		if ( $kintVar->name !== null && $kintVar->name !== '' ) {
			$output .= ' ' . $kintVar->name;
		}

		if ( $kintVar->operator ) {
			$output .= ' ' . $kintVar->operator;
		}

		if ( self::$_enableColors ) {
			$output .= " \033[1;35m" . $kintVar->type . "\033[0m";
		} else {
			$output .= $kintVar->type;
		}

		if ( $kintVar->subtype ) {
			$output .= ' ' . $kintVar->subtype;
		}


		if ( $kintVar->size !== null ) {
			$output .= ' (' . $kintVar->size . ')';
		}


		if ( $kintVar->value !== null && $kintVar->value !== '' ) {
			$output .= ' ' . $kintVar->value;
		}

		return ltrim( $output );
	}

	private static function _buildCalleeString( $callee )
	{
		list( , $shortenedName ) = self::shortenPath( $callee['file'], $callee['line'], false );

		return $shortenedName;
	}

	private static function _init()
	{
		if ( !isset( self::$_enableColors ) ) {
			self::$_enableColors = strtoupper( substr( PHP_OS, 0, 3 ) ) !== 'WIN' || getenv( 'ANSICON' );
		}
	}
}