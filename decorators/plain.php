<?php

class Kint_Decorators_Plain
{
	private static $_enableColors;

	private static $_cliEffects      = array(
		# effects
		'bold'             => '1', 'dark' => '2',
		'italic'           => '3', 'underline' => '4',
		'blink'            => '5', 'reverse' => '7',
		'concealed'        => '8', 'default' => '39',

		# colors
		'black'            => '30', 'red' => '31',
		'green'            => '32', 'yellow' => '33',
		'blue'             => '34', 'magenta' => '35',
		'cyan'             => '36', 'light_gray' => '37',
		'dark_gray'        => '90', 'light_red' => '91',
		'light_green'      => '92', 'light_yellow' => '93',
		'light_blue'       => '94', 'light_magenta' => '95',
		'light_cyan'       => '96', 'white' => '97',

		# backgrounds
		'bg_default'       => '49', 'bg_black' => '40',
		'bg_red'           => '41', 'bg_green' => '42',
		'bg_yellow'        => '43', 'bg_blue' => '44',
		'bg_magenta'       => '45', 'bg_cyan' => '46',
		'bg_light_gray'    => '47', 'bg_dark_gray' => '100',
		'bg_light_red'     => '101', 'bg_light_green' => '102',
		'bg_light_yellow'  => '103', 'bg_light_blue' => '104',
		'bg_light_magenta' => '105', 'bg_light_cyan' => '106',
		'bg_white'         => '107',
	);
	private static $_utfSymbols      = array(
		'┌', '▄', '┐',
		'│',
		'└', '─', '┘'
	);
	private static $_winShellSymbols = array(
		"\xda", "\xdc", "\xbf",
		"\xb3",
		"\xc0", "\xc4", "\xd9"
	);
	private static $_htmlSymbols     = array(
		"&#9484;", "&#9604;", "&#9488;",
		"&#9474;",
		"&#9492;", "&#9472;", "&#9496;"
	);

	public static function decorate( kintVariableData $kintVar, $level = 0 )
	{
		$output = '';
		if ( $level === 0 ) {
			$name          = $kintVar->name ? $kintVar->name : 'literal';
			$kintVar->name = null;

			$output .= self::_title( $name );
		}


		$space = str_repeat( $s = '    ', $level );
		$output .= $space . self::_drawHeader( $kintVar );


		if ( $kintVar->extendedValue !== null ) {
			$output .= ' ' . ( $kintVar->type === 'array' ? '[' : '(' ) . PHP_EOL;


			if ( is_array( $kintVar->extendedValue ) ) {
				foreach ( $kintVar->extendedValue as $v ) {
					$output .= self::decorate( $v, $level + 1 );
				}
			} elseif ( is_string( $kintVar->extendedValue ) ) {
				$output .= $space . $s . $kintVar->extendedValue . PHP_EOL; # depth too great or similar
			} else {
				$output .= self::decorate( $kintVar->extendedValue, $level + 1 ); //it's kintVariableData
			}
			$output .= $space . ( $kintVar->type === 'array' ? ']' : ')' ) . PHP_EOL;
		} else {
			$output .= PHP_EOL;
		}

		return $output;
	}

	public static function decorateTrace( $traceData )
	{
		$output   = self::_title( 'TRACE' );
		$lastStep = count( $traceData );
		foreach ( $traceData as $stepNo => $step ) {
			$title = str_pad( ++$stepNo . ': ', 4, ' ' );

			$title .= self::_colorize(
				( isset( $step['file'] ) ? self::_buildCalleeString( $step ) : 'PHP internal call' ),
				'cyan'
			);

			if ( !empty( $step['function'] ) ) {
				$title .= '    ' . $step['function'];
				if ( isset( $step['args'] ) ) {
					$title .= '(';
					if ( empty( $step['args'] ) ) {
						$title .= ')';
					} else {
					}
					$title .= PHP_EOL;
				}
			}

			$output .= $title;

			if ( !empty( $step['args'] ) ) {

				foreach ( $step['args'] as $name => $argument ) {
					$argument           = kintParser::factory( $argument, '$' . $name );
					$argument->operator = '=';
					$maxLevels          = Kint::$maxLevels;
					if ( $maxLevels ) {
						Kint::$maxLevels = $maxLevels + 2;
					}
					$output .= self::decorate( $argument, 2 );
					if ( $maxLevels ) {
						Kint::$maxLevels = $maxLevels;
					}
				}
				$output .= '    )' . PHP_EOL;
			}

			if ( !empty( $step['object'] ) ) {
				$output .= self::_colorize(
					'    ' . self::_char( '─', 27 ) . ' Callee object ' . self::_char( '─', 34 ),
					'cyan'
				);

				$maxLevels = Kint::$maxLevels;
				if ( $maxLevels ) {
					Kint::$maxLevels = $maxLevels + 1;
				}
				$output .= self::decorate( kintParser::factory( $step['object'] ), 1 );
				if ( $maxLevels ) {
					Kint::$maxLevels = $maxLevels;
				}
			}

			if ( $stepNo !== $lastStep ) {
				$output .= self::_colorize( self::_char( '─', 80 ), 'cyan' );
			}
		}

		return $output;
	}


	private static function _colorize( $text, $options, $nlAfter = true )
	{
		$nlAfter = $nlAfter ? PHP_EOL : '';

		if ( Kint::enabled() === Kint::MODE_PLAIN && strpos( $options, 'bold' ) !== false ) {
			$text = "<b>{$text}</b>";
		}

		if ( !self::$_enableColors || Kint::enabled() !== Kint::MODE_CLI ) return $text . $nlAfter;

		return
			"\x1b["
			. strtr( $options, self::$_cliEffects + array( ' ' => ';' ) ) . 'm' . $text . "\x1b[0m"
			. $nlAfter;
	}


	private static function _char( $char, $repeat = null )
	{
		$inWindowsShell = PHP_SAPI === 'cli' && DIRECTORY_SEPARATOR !== '/';
		if ( $inWindowsShell ) {
			$char = self::$_winShellSymbols[ array_search( $char, self::$_utfSymbols, true ) ];
		} elseif ( Kint::enabled() === Kint::MODE_PLAIN ) {
			$char = self::$_htmlSymbols[ array_search( $char, self::$_utfSymbols, true ) ];
		}

		return $repeat ? str_repeat( $char, $repeat ) : $char;
	}

	private static function _title( $text )
	{
		return
			self::_colorize(
				self::_char( '┌' ) . self::_char( '─', 78 ) . self::_char( '┐' ) . PHP_EOL
				. self::_char( '│' ),
				'cyan',
				false
			)

			. self::_colorize( str_pad( $text, 78, ' ', STR_PAD_BOTH ), 'cyan bold', false )

			. self::_colorize( self::_char( '│' ) . PHP_EOL
				. self::_char( '└' ) . self::_char( '─', 78 ) . self::_char( '┘' ),
				'cyan'
			);
	}

	public static function wrapStart()
	{
		if ( Kint::enabled() === Kint::MODE_PLAIN ) {
			return '<pre>';
		}
		return '';
	}

	public static function wrapEnd( $callee, $miniTrace, $prevCaller )
	{
		$lastLine = self::_colorize( self::_char( "▄", 80 ), 'cyan' );
		$lastChar = Kint::enabled() === Kint::MODE_PLAIN ? '</pre>' : '';


		if ( !Kint::$displayCalledFrom ) return $lastLine . $lastChar;

		list( $url, $shortenedName ) = Kint::shortenPath( $callee['file'], $callee['line'], false );

		if ( !$shortenedName ) return $lastLine . $lastChar;

		if ( Kint::enabled() === Kint::MODE_PLAIN ) {
			if ( strpos( $url, 'http://' ) === 0 ) {
				$calleeInfo = "<a href=\"#\"onclick=\""
					. "X=new XMLHttpRequest;"
					. "X.open('GET','{$url}');"
					. "X.send();"
					. "return!1\">{$shortenedName}</a>";
			} else {
				$calleeInfo = "<a href=\"{$url}\">{$shortenedName}</a>";
			}
		} else {
			$calleeInfo = $shortenedName;;
		}

		return $lastLine . self::_colorize( "Called from {$calleeInfo}", 'cyan' ) . $lastChar;
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

		$output .= ' ' . self::_colorize( $kintVar->type, 'magenta bold', false );

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
		list( , $shortenedName ) = Kint::shortenPath( $callee['file'], $callee['line'], false );


		return $shortenedName;
	}

	public static function init()
	{
		self::$_enableColors =
			Kint::$cliColors
			&& ( DIRECTORY_SEPARATOR === '/' || getenv( 'ANSICON' ) !== false || getenv( 'ConEmuANSI' ) === 'ON' );
	}
}