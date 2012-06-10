<?php

// TODO

class Kint_Parsers_Callable extends kintParser
{
	protected function _parse( & $variable, $options )
	{
		function ReflectionFunctionFactory( $callback )
		{
			if ( is_array( $callback ) ) {
				// must be a class method
				list( $class, $method ) = $callback;
				return new ReflectionMethod( $class, $method );
			}

			// class::method syntax
			if ( is_string( $callback ) && strpos( $callback, "::" ) !== false ) {
				list( $class, $method ) = explode( "::", $callback );
				return new ReflectionMethod( $class, $method );
			}

			// objects as functions (PHP 5.3+)
			if ( version_compare( PHP_VERSION, "5.3.0", ">=" ) && method_exists( $callback, "__invoke" ) ) {
				return new ReflectionMethod( $callback, "__invoke" );
			}

			// assume it's a function
			return new ReflectionFunction( $callback );
		}
	}

}
