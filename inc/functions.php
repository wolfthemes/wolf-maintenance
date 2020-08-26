<?php

/**
 * Clean a list
 *
 * Remove first and last comma of a list and remove spaces before and after separator
 *
 * @param string $list
 * @return string $list
 */
function wm_clean_list( $list, $separator = ',' ) {
	$list = str_replace( array( $separator . ' ', ' ' . $separator ), $separator, $list );
	$list = ltrim( $list, $separator );
	$list = rtrim( $list, $separator );
	return $list;
}

/**
 * Remove all double spaces and line breaks
 *
 * This function is mainly used to clean up inline CSS
 *
 * @param string $css
 * @return string
 */
function wm_clean_spaces( $string, $hard = false ) {

	if ( $hard ) {
		return str_replace( ' ', '', $string );
	} else {
		return preg_replace( '/\s+/', ' ', $string );
	}
}

/**
 * Convert list to array
 *
 * @param string $list
 * @return array
 */
function wm_list_to_array( $list, $separator = ',' ) {
	return ( $list ) ? explode( ',', trim( wm_clean_spaces( wm_clean_list( $list ) ) ) ) : array();
}

/**
 * Convert array of ids to list
 *
 * @param string $list
 * @return array
 */
function wm_array_to_list( $array, $separator = ',' ) {
	$list = '';

	if ( is_array( $array ) ) {
		$list = rtrim( implode( $separator, array_unique( $array ) ), $separator );
	}

	return wm_clean_list( $list );
}
