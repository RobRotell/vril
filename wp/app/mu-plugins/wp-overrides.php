<?php


/**
 * Plugin Name: 	WP Overrides
 * Plugin URI: 		https://robrotell.com
 * Description: 	Adds various overrides to core WP
 * Version: 		0.0.1
 * Author: 			Rob Rotell
 * Author URI: 		https://robrotell.com
 *
 * Text Domain: 	vril
 */


defined( 'ABSPATH' ) || exit;



// remove unneeded menu items
add_action( 'admin_menu', function() {
	remove_menu_page( 'edit-comments.php' );
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'edit.php?post_type=page' );

	remove_submenu_page( 'tools.php', 'site-health.php' );
});


// block access to site health in admin
add_action( 'current_screen', function() {
	if( is_admin() ) {
		$screen = get_current_screen();

		if( 'site-health' === $screen->id ) {
			wp_redirect( admin_url() );
			exit;
		}
	}
});


// remove WP REST link header
add_action( 'after_setup_theme', function() {
	remove_action( 'template_redirect', 'rest_output_link_header', 11, 0 );
});


// disable unused sidebar menu items
add_action( 'wp_before_admin_bar_render', function() {
	global $wp_admin_bar;

	$wp_admin_bar->remove_node( 'about' );
	$wp_admin_bar->remove_node( 'documentation' );
	$wp_admin_bar->remove_node( 'feedback' );
	$wp_admin_bar->remove_node( 'new-media' );
	$wp_admin_bar->remove_node( 'new-page' );
	$wp_admin_bar->remove_node( 'new-post' );
	$wp_admin_bar->remove_node( 'new-user' );
	$wp_admin_bar->remove_node( 'support-forums' );
	$wp_admin_bar->remove_node( 'wp-logo' );  
	$wp_admin_bar->remove_node( 'wporg' );
    $wp_admin_bar->remove_node( 'comments' ); 

	$node = $wp_admin_bar->get_node( 'my-account' );
	
	// remove original node
	$wp_admin_bar->remove_node( 'my-account' );
	
	// change "Howdy" text
	$node->title = str_replace( 'Howdy, ', '', $node->title );
	$wp_admin_bar->add_node( $node );

	// remove Comments menu item
	$wp_admin_bar->remove_menu( 'comments' );

}, 9999 );


// remove "Thanks for creating ..." text in WP admin
add_filter( 'admin_footer_text', '__return_false' );


// remove WP version number in WP admin
add_filter( 'update_footer', '__return_false', 9999 );


// remove link text on login
add_filter( 'login_headertext', '__return_false' );


// change URL for login logo
add_filter( 'login_headerurl', 'get_home_url' );


// preload admin logo
add_action( 'login_head', function() {
	printf( '<link rel="preload" as="image" href="%s" />', wp_get_attachment_url( 3012 ) );
}, -99 );


// change logo on login page
add_action( 'login_head', function() {
	$logo_url = wp_get_attachment_url( 3012 );

	?>

	<style>
		.login {
			background-color: #f4f4f4;
		}

		#login {
			max-width: 90%;
			width: 360px;
		}

		#loginform {
			margin-top: 40px;
			padding: 32px 42px 42px;
			border: none;
			border-radius: 6px;
			box-shadow: none;
		}

		.login form .input, 
		.login input[type="password"], 
		.login input[type="text"] {
			font-size: 18px;
			padding: 4px 8px;
			border: none;
			background-color: #f4f4f4;
		}

		.login form .input:focus {
			box-shadow: none;
			outline: none;
			border: 1px solid #663b94;
		}

		#wp-submit {
			background-color: #890709;
			border: none;
			transition: background-color .5s linear;
		}
		
		#wp-submit:hover {
			background-color: #CF1021;
			transition: background-color .175s linear;
		}

		.login h1 a {
			background-image: url( '<?php echo $logo_url; ?>' );
			background-size: contain;
			width: 125px;
			height: 125px;
		}
	</style>

	<?php
}, 9999 );


// remove "Go to ..." link on login
add_filter( 'login_site_html_link', '__return_false' );


// remove "lost your password?" link
add_filter( 'clean_url', function( string $url = '' ): string {
	if( str_contains( $url, 'action=lostpassword' ) ) {
		$url = '';
	}

	return $url;
}, 9999 );


// remove "lost your password?" text
add_filter( 'gettext', function( string $translation = '' ): string {
	if( did_action( 'login_head' ) && 'Lost your password?' === $translation ) {
		$translation = '';
	}

	return $translation;
}, 9999 );


// remove WordPress from document title
add_filter( 'gettext', function( string $translation, string $text ) {
	if( '%1$s &lsaquo; %2$s &#8212; WordPress' === $text ) {
		$translation = 'Vril | Control';
	}

	return $translation;
}, 9999, 2 );



/**
 * Block unneeded REST API endpoints
 *
 * @param	array 	$endpoints 	Endpoints
 * @return 	array 				Filtered endpoints
 */
// add_filter( 'rest_endpoints', function( $endpoints ) {

// 	// allow all endpoints for logged in admins
// 	$current_user = wp_get_current_user();
// 	if( in_array( 'administrator', $current_user->roles ) ) {
// 		return $endpoints;
// 	}

// 	foreach( $endpoints as $route => $endpoint ) {
// 		if( !apply_filters( 'vril_whitelist_rest_route', false, $route ) ) {
// 			unset( $endpoints[ $route ] );
// 		}
// 	}

// 	return $endpoints;
// }, 9999 );