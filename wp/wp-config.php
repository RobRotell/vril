<?php

define( 'ROBR_APP', 'VRIL' );
require_once( __DIR__ . '/vril-config.env' );


define( 'DB_NAME',			VRIL_DB_NAME );
define( 'DB_USER', 			VRIL_DB_USER );
define( 'DB_PASSWORD',		VRIL_DB_PASSWORD );
define( 'DB_HOST',			VRIL_DB_HOST );
define( 'DB_CHARSET',		VRIL_DB_CHARSET );
define( 'DB_COLLATE', 		VRIL_DB_COLLATE );

define( 'AUTH_KEY',			VRIL_AUTH_KEY );
define( 'SECURE_AUTH_KEY',	VRIL_SECURE_AUTH_KEY );
define( 'LOGGED_IN_KEY',	VRIL_LOGGED_IN_KEY );
define( 'NONCE_KEY',		VRIL_NONCE_KEY );
define( 'AUTH_SALT',		VRIL_AUTH_SALT );
define( 'SECURE_AUTH_SALT',	VRIL_SECURE_AUTH_SALT );
define( 'LOGGED_IN_SALT',	VRIL_LOGGED_IN_SALT );
define( 'NONCE_SALT',		VRIL_NONCE_SALT );

$table_prefix = VRIL_DB_PREFIX;

define( 'WP_DEBUG', 		true );
define( 'WP_DEBUG_LOG', 	true );
define( 'WP_DEBUG_DISPLAY', true );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );

if( !defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

define ('WP_CONTENT_FOLDER_NAME',	'app');
define( 'WP_CONTENT_DIR', 			ABSPATH . 'app' );
define( 'WP_CONTENT_URL', 			sprintf( 'https://%s/%s', $_SERVER['HTTP_HOST'], WP_CONTENT_FOLDER_NAME ) );
define( 'MUPLUGINDIR', 				WP_CONTENT_DIR . '/mu-plugins' );
define( 'WP_PLUGIN_DIR', 			realpath( WP_CONTENT_DIR . '/plugins' ) );
define( 'UPLOADS', 					'app/assets' );


require_once ABSPATH . 'wp-settings.php';
