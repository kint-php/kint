<?php

class Kint_Objects_Closure extends KintObject
{
	public function parse( & $variable )
	{
		if ( !$variable instanceof Closure ) return false;

		$this->name = 'Closure';
		$reflection = new ReflectionFunction( $variable );
		$ret        = array(
			'Parameters' => array()
		);
		if ( $val = $reflection->getParameters() ) {
			foreach ( $val as $parameter ) {
				// todo http://php.net/manual/en/class.reflectionparameter.php
				$ret['Parameters'][] = $parameter->name;
			}

		}
		if ( $val = $reflection->getStaticVariables() ) {
			$ret['Uses'] = $val;
		}
		if ( method_exists($reflection, 'getClousureThis') && $val = $reflection->getClosureThis() ) {
			$ret['Uses']['$this'] = $val;
		}
		if ( $val = $reflection->getFileName() ) {
			$this->value = Kint::shortenPath( $val ) . ':' . $reflection->getStartLine();
		}

		return $ret;
	}


	public function isDefaultValueAvailable()
	{
		if ( PHP_VERSION_ID === 50316 ) { // PHP bug #62988
			try {
				$this->getDefaultValue();
				return true;
			} catch ( \ReflectionException $e ) {
				return false;
			}
		}
		return parent::isDefaultValueAvailable();
	}
}
