<?php


/**
 * Plugin Name: 	Vril Utility
 * Plugin URI: 		https://robrotell.com
 * Description: 	Adds utility functions to support plugins
 * Version: 		0.0.1
 * Author: 			Rob Rotell
 * Author URI: 		https://robrotell.com
 *
 * Text Domain: 	vril
 */


defined( 'ABSPATH' ) || exit;


final class Vril_Utility
{
	/**
	 * Simple, recursive sanitizing function
	 *
	 * @param	mixed	$var 	Object/array/string sanitize
	 * @return	mixed 			Sanitized var
	 */
	public static function sanitize_var( $var ) 
	{
		if( is_string( $var ) ) {
			$var = trim( sanitize_text_field( $var ) );
		} elseif( is_object( $var ) ) {
			$var = (array)$var;
		} 

		if( is_array( $var ) ) {
			$var_clean = [];

			foreach( $var as $key => $value ) {
				$key 	= self::sanitize_var( $value );
				$value 	= self::sanitize_var( $value );

				$var_clean[ $key ] = $value;
			}
		} 

		return $var;
	}


	/**
	 * Convert value to boolean
	 *
	 * @param	mixed 	$var 	Variable to convert to boolean
	 * @return 	boolean 		Boolean
	 */
	public static function convert_to_bool( $var ): bool
	{
		$result = in_array(
			$var,
			[ 1, '1', true, 'true' ],
			true
		);

		return $result;
	}	


	/**
	 * Simple integer-converting function
	 *
	 * @param	mixed	$var 	Variable to convert to integer
	 * @return	integer 		Integer
	 */	
	public static function convert_to_int( $var ): int 
	{
		if( is_array( $var ) && 1 === count( $var ) ) {
			return self::convert_to_int( $var );
		}

		return (int)$var;
	}


	/**
	 * Convert value to an array
	 *
	 * @param	mixed 	$arg 		Arg to turn into an array
	 * @param 	string 	$separator 	Text separator
	 * 
	 * @return 	array 				Resulting array
	 */
	public static function convert_to_array( $arg, string $separator = ',' ): array
	{
		if( is_string( $arg ) ) {
			if( !empty( $maybe_json = json_decode( $arg, true ) ) ) {
				$arg = $maybe_json;
			} else {
				$arg = explode( $separator, $arg );
			}
		} elseif( is_int( $arg ) || is_float( $arg ) || is_object( $arg ) ) {
			$arg = (array)$arg;
		} else {
			$arg = [];
		}

		$arg = self::sanitize_var( $arg );

		return $arg;
	}

}