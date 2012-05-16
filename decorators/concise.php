<?php
class kintConciseDecorator extends Kint
{
	/**
	 * output:
	 *
	 * [value]
	 *
	 * value [title="[access] [name] [operator *] [subtype] [size] "]
	 * OR
	 * type [title="[access] [name] type [operator *] [subtype] [size] "]
	 * <ul>extendedValue
	 *
	 * @param kintParser $kintVar
	 *
	 * @return string
	 */
	public static function decorate( kintParser $kintVar )
	{
		if ( is_array( $kintVar->value ) || $kintVar->extendedValue !== null ) {
			return self::$_richDecorator->decorate( $kintVar );
		}

		$output = '<span title="';

		if ( $kintVar->access !== null ) {
			$output .= $kintVar->access . " ";
		}

		if ( $kintVar->name !== null ) {
			$output .= $kintVar->name . " ";
		}

		if ( $kintVar->type !== null ) {
			$output .= $kintVar->type;
			if ( $kintVar->subtype !== null ) {
				$output .= " " . $kintVar->subtype;
			}
			$output .= " ";
		}

		if ( $kintVar->operator !== null ) {
			$output .= $kintVar->operator . "";
		}

		if ( $kintVar->size !== null ) {
			$output .= "(" . $kintVar->size . ") ";
		}

		$output .= '">' . $kintVar->value . '</span>';

		return $output;
	}
}