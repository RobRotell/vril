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
	 * @param	string 	$var 	Variable to convert to boolean
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
	 * Wrapper for var_dump to make it easier to trace where var_dump commands are being invoked
	 *
	 * @param	mixed 	$var 	Variables
	 * @return 	void
	 */
	public static function vd( ...$args ): void 
	{
		foreach( $args as $arg ) {
			$caller = debug_backtrace()[0];

			printf( '<pre data-fl="%s:%s"><code>', $caller['file'], $caller['line'] ); 
			var_dump( $arg ); 
			echo '</code></pre>';
		}
	}


}