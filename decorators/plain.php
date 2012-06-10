<?php
class Kint_Decorators_Plain extends Kint
{
	/**
	 * output:
	 *
	 * [access *] [name] type [operator *] [subtype] [size] [value]
	 *
	 * @param kintParser $kintVar
	 *
	 * @return string
	 */
	protected static function decorate( $kintVar )
	{
	}


	/**
	 * produces css and js required for display. May be called multiple times, will only produce output once per
	 * pageload or until `-` or `@` modifier is used
	 *
	 * @return string
	 */
	protected static function _css()
	{
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
	}


	/**
	 * closes Kint::_wrapStart() started html tags and displays callee information
	 *
	 * @param array $callee caller information taken from debug backtrace
	 * @param array $prevCaller previous caller information taken from debug backtrace
	 *
	 * @return string
	 */
	private static function _wrapEnd( $callee, $prevCaller )
	{
	}

}