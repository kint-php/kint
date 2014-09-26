<?php

/**
 * used only for array tabular display
 */
class Kint_Decorators_Concise
{
	public static function decorate( kintVariableData $kintVar )
	{
		if ( $kintVar->extendedValue !== null || !empty( $kintVar->_alternatives ) ) {
			return Kint_Decorators_Rich::decorate( $kintVar );
		}

		if ( $kintVar->value !== null ) {
			$output = '<p title="';

			if ( $kintVar->access !== null ) {
				$output .= $kintVar->access . " ";
			}

			if ( $kintVar->name !== null ) {
				$output .= $kintVar->name . " ";
			}

			if ( $kintVar->type !== null ) {
				$output .= $kintVar->type . " ";
			}

			if ( $kintVar->operator !== null ) {
				$output .= $kintVar->operator . "";
			}

			if ( $kintVar->size !== null ) {
				$output .= "(" . $kintVar->size . ") ";
			}

			$output = trim( $output ) . '">' . $kintVar->value . '</p>';
		} else {
			$output = '<u>NULL</u>';
		}

		return $output;
	}
}