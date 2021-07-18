<?php

define( 'ROBR_APP', 'VRIL' );
require_once( __DIR__ . '/vril-config.env' );


define( 'DB_NAME',			VRIL_DB_NAME );
define( 'DB_USER', 			VRIL_DB_USER );
define( 'DB_PASSWORD',		VRIL_DB_PASSWORD );
define( 'DB_HOST',			VRIL_DB_HOST );
define( 'DB_CHARSET',		VRIL_DB_CHARSET );
define( 'DB_COLLATE', 		VRIL_DB_COLLATE );

define('AUTH_KEY',			'(z,X<|cQi;R>4do`>R@:R-wRGHlB1gb]<(erVQ+xOrRjSRy(*cCQX},9gg*ZtK>&');
define('SECURE_AUTH_KEY',	'SJ,L+ Yb<|1WLo4dqtwzr%P7 JW/ :Q$zD.;-E+wS8~^!.H{MVo3JVkxzi-Au95f');
define('LOGGED_IN_KEY',		'g/}w+K$;25?T@|+KoBJynw@9>cfuDYS9+ul;#^D`y*ce3;ds[+noJ^E,Vso(`~GS');
define('NONCE_KEY',			'NP%Y!h-5.Iz&w0(<-2jlMK{{=$Q0@3borEB<u@L[/g!e}$tYL,Xgv^v]5P+DVD>P');
define('AUTH_SALT',			'L-`OZxAXvwB9/m`YHDVO&hkXP!c#2!#6eg+6C*+s`6o$))?r&pSEp`Uu5DD7_ rJ');
define('SECURE_AUTH_SALT',	'(iaRO}CpF+9D~V91)o)$$y|U5 2xp~aaj`|^!mMgzFV|A1P$K7k-MSo[t3+EWA$#');
define('LOGGED_IN_SALT',	'J}$8bHc{_=aH6Q,2/+H)Unb!dhhdQba)_tRC]A6CQ&io,RM!VO@fBwh?K)hcR`rY');
define('NONCE_SALT',		'AnL[~Eed@mR&vA~a/0OgC5 {QZ)-IA/?|&2DQ}n11UG+-|wfdz|t<9G5So{:]kEy');

$table_prefix = VRIL_DB_PREFIX;

define( 'WP_DEBUG', 		true );
define( 'WP_DEBUG_LOG', 	true );
define( 'WP_DEBUG_DISPLAY', true );

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
