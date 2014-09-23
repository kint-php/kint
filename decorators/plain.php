<?php

class Kint_Decorators_Plain
{
	private static $_enableColors;
	private static $_firstInGroup;

	# to distinguish brackets in long outputs
	private static $_bracketStyles = array(
		'bg_green black', # iteration starts from the next element, this one's last

		'bg_default',

		'bg_black red', 'bg_black green', 'bg_black yellow', 'bg_black blue', 'bg_black magenta',
		'bg_black cyan', 'bg_black white',

		'bg_light_gray red', 'bg_light_gray green', 'bg_light_gray yellow', 'bg_light_gray blue', 'bg_light_gray magenta',
		'bg_light_gray cyan', 'bg_light_gray black',

		'bg_red green', 'bg_red yellow', 'bg_red blue', 'bg_red cyan', 'bg_red white', 'bg_red black',
	);

	private static $_effects = array(
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

	public static function decorate( kintVariableData $kintVar, $level = 0 )
	{
		$output = '';
		if ( $level === 0 ) {
			$name          = $kintVar->name ? $kintVar->name : 'literal';
			$kintVar->name = null;

			$output .= self::_padTitle( $name, 'green' );
		}


		$space = str_repeat( $s = '    ', $level );
		$output .= $space . self::_drawHeader( $kintVar );


		if ( $kintVar->extendedValue !== null ) {
			$bracketColor = next( self::$_bracketStyles );
			$bracketColor or $bracketColor = reset( self::$_bracketStyles );
			$output .= ' ' . self::_colorize( "[", $bracketColor );


			if ( is_array( $kintVar->extendedValue ) ) {
				foreach ( $kintVar->extendedValue as $v ) {
					$output .= self::decorate( $v, $level + 1 );
				}
			} elseif ( is_string( $kintVar->extendedValue ) ) {
				$output .= $space . $s . $kintVar->extendedValue . PHP_EOL; # depth too great or similar
			} else {
				$output .= self::decorate( $kintVar->extendedValue, $level + 1 ); //it's kintVariableData
			}
			$output .= $space . self::_colorize( "]", $bracketColor );
		} else {
			$output .= PHP_EOL;
		}

		return $output;
	}

	public static function decorateTrace( $traceData )
	{
		$output   = self::_padTitle( 'TRACE', 'green' );
		$needsTop = true;
		foreach ( $traceData as $i => $step ) {
			$stepNo = $i + 1;


			$title = str_pad( $stepNo . ': ', 4, ' ' );

			$title .= isset( $step['file'] )
				? self::_buildCalleeString( $step )
				: 'PHP internal call';

			$title .= ' ' . $step['function'];

			if ( isset( $step['args'] ) ) {
				$title .= '(' . implode( ', ', array_keys( $step['args'] ) ) . ')';
			}

			if ( $needsTop ) {
				$output .= self::_colorize( str_repeat( self::_singleSeparator(), 80 ), 'cyan' );
				$needsTop = false;
			}

			$output .= self::_colorize( $title, 'cyan' );
			$output .= self::_colorize( str_repeat( self::_singleSeparator(), 80 ), 'cyan' );


			if ( !empty( $step['args'] ) ) {
				$needsTop = true;

				$output .= self::_colorize( "Arguments (step #{$stepNo}):", 'green' );
				$output .= self::decorate( kintParser::factory( $step['args'] ), 1 );
			}

			if ( !empty( $step['object'] ) ) {
				$needsTop = true;

				$output .= self::_colorize( "Callee object (step #{$stepNo}):", 'green' );
				$output .= self::decorate( kintParser::factory( $step['object'] ), 1 );
			}
		}

		return $output;
	}


	private static function _colorize( $text, $options, $nlAfter = true )
	{
		$nlAfter = $nlAfter ? PHP_EOL : '';
		if ( !self::$_enableColors ) return $text . $nlAfter;

		return
			"\x1b["
			. strtr( $options, self::$_effects + array( ' ' => ';' ) ) . 'm' . $text . "\x1b[0m"
			. $nlAfter;
	}

	private static function _doubleSeparator()
	{
		return PHP_SAPI === 'cli' && DIRECTORY_SEPARATOR !== '/' ? "\xcd" : "═";
	}

	private static function _singleSeparator()
	{
		return PHP_SAPI === 'cli' && DIRECTORY_SEPARATOR !== '/' ? "\xc4" : "─";
	}

	private static function _padTitle( $text, $color )
	{
		$ret = self::_colorize(
			( self::$_firstInGroup ? '' : str_repeat( self::_singleSeparator(), 80 ) . PHP_EOL )
			. str_pad( $text, 80, ' ', STR_PAD_BOTH ) . PHP_EOL
			. str_repeat( self::_singleSeparator(), 80 ),
			$color
		);

		self::$_firstInGroup = false;
		return $ret;
	}

	public static function wrapStart( $callee )
	{
		self::$_firstInGroup = true;
		$wrapper             = Kint::enabled() === Kint::MODE_PLAIN ? '<pre>' . PHP_EOL : '';
		return $wrapper . self::_colorize( str_repeat( self::_doubleSeparator(), 80 ), 'green' );
	}

	public static function wrapEnd( $callee, $miniTrace, $prevCaller )
	{
		$endChar = Kint::enabled() === Kint::MODE_PLAIN ? '</pre>' : PHP_EOL;

		if ( !Kint::$displayCalledFrom ) return $endChar;

		list( $url, $shortenedName ) = Kint::shortenPath( $callee['file'], $callee['line'], false );

		if ( !$shortenedName ) return $endChar;

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

		return self::_colorize(
			str_repeat( self::_singleSeparator(), 80 ) . PHP_EOL
			. "Called from {$calleeInfo}" . PHP_EOL
			. str_repeat( self::_doubleSeparator(), 80 ),
			'green'
		) . $endChar;
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

		$output .= ' ' . self::_colorize( $kintVar->type, 'magenta', false );

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
		list( $url, $shortenedName ) = Kint::shortenPath( $callee['file'], $callee['line'], false );

		return $shortenedName;
	}

	public static function init()
	{
		self::$_enableColors =
			Kint::$cliColors
			&& Kint::enabled() === Kint::MODE_CLI
			&& ( DIRECTORY_SEPARATOR === '/' || getenv( 'ANSICON' ) !== false || getenv( 'ConEmuANSI' ) === 'ON' );
	}
}