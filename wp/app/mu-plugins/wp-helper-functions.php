<?php


/**
 * Plugin Name: 	WP Helper Functions
 * Plugin URI: 		https://robrotell.com
 * Description: 	Adds helper functions to help plugins
 * Version: 		0.0.1
 * Author: 			Rob Rotell
 * Author URI: 		https://robrotell.com
 *
 * Text Domain: 	vril
 */


defined( 'ABSPATH' ) || exit;


/**
 * Wrapper for var_dump to make it easier to trace where var_dump commands are being invoked
 *
 * @param	mixed 	$var 	Variables
 * @return 	void
 */
function vd( ...$args ): void {
	foreach( $args as $arg ) {
		$caller = debug_backtrace()[0];

		printf( '<pre data-fl="%s:%s"><code>', $caller['file'], $caller['line'] ); 
		var_dump( $arg ); 
		echo '</code></pre>';
	}
}