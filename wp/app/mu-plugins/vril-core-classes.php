<?php


/**
 * Plugin Name: 	Vril Core Classes
 * Plugin URI: 		https://robrotell.com
 * Description: 	Adds common classes to be used across plugins
 * Version: 		0.0.1
 * Author: 			Rob Rotell
 * Author URI: 		https://robrotell.com
 *
 * Text Domain: 	vril
 */


defined( 'ABSPATH' ) || exit;


include_once( __DIR__ . '/vril-core-classes/abstract-auth-tokens.php' );
include_once( __DIR__ . '/vril-core-classes/abstract-rest-api-endpoint.php' );
include_once( __DIR__ . '/vril-core-classes/class-rest-api-response.php' );
