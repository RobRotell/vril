<?php


namespace Loa\Controller;


use Exception;
use Vril_Utility;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;


defined( 'ABSPATH' ) || exit;


class Endpoint
{
	const NAMESPACE = 'loa/v3';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
		add_action( 'rest_api_init', 	[ $this, 'register_routes' ], 9999 );
		add_filter( 'http_origin', 		[ $this, 'extension_origin_fix' ] );
	}


	/**
	 * Get endpoint URL
	 *
	 * @return 	string 	Endpoint URL
	 */
	public static function get_endpoint_url()
	{
		return get_rest_url( null, self::NAMESPACE );
	}	


	/**
	 * Check if user is authorized for action
	 *
	 * @param	WP_REST_Request	$auth 	Submitted authorization code
	 * @return	WP_Error|bool 			WP error if unauthorized; otherwise, true
	 */
	public static function check_auth( WP_REST_Request $request )
	{
		$auth = $request->get_param( 'auth' ) ?: '';

		return Loa()->admin::check_auth( $auth );
	}	


	/**
	 * Register routes for endpoint
	 *
	 * @return 	void
	 */
	public function register_routes(): void
	{
		$api = Loa()->api;

		// grab subset of articles
		register_rest_route(
			self::NAMESPACE,
			'/get-articles',
			[
				'methods'				=> WP_REST_Server::READABLE,
				'callback'				=> [ $api, 'get_articles' ],
				'permission_callback'	=> '__return_true',
				'args'		=> [
					'page'	=> [
						'default'			=> 1,
						'type'				=> 'string',
						'sanitize_callback'	=> 'absint',
					],
					'count'	=> [
						'default'			=> 50,
						'type'				=> 'string',
						'sanitize_callback'	=> 'absint',
					],
					'tag'	=> [
						'default'			=> 0,
						'type'				=> 'string',
						'sanitize_callback'	=> 'absint', // @todo — could be array
					],
					'keyword' => [
						'default'			=> '',
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'sanitize_var' ],
					],
					'read' => [
						'default'			=> false,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					],
					'favorite' => [
						'default'			=> false,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					],					
				]
			]
		);

		// grab all tags
		register_rest_route(
			self::NAMESPACE,
			'/get-tags',
			[
				'callback'				=> [ $api, 'get_tags' ],
				'methods'				=> WP_REST_Server::READABLE,
				'permission_callback' 	=> '__return_true',
			]
		);		

		// grab time from last update
		register_rest_route(
			self::NAMESPACE,
			'/get-last-updated-time',
			[
				'callback'				=> [ $api, 'get_last_updated_time' ],
				'methods'				=> WP_REST_Server::READABLE,
				'permission_callback'	=> '__return_true',
			]
		);	

		// update article status
		register_rest_route(
			self::NAMESPACE,
			'/update-article',
			[
				'callback'				=> [ $api, 'update_article' ],
				'methods'				=> WP_REST_Server::EDITABLE,
				'permission_callback'	=> [ $this, 'check_auth' ],
				'args' 					=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback' => [ 'Vril_Utility', 'sanitize_var' ],
					],
					'id'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback' => 'absint',
					],					
					'read'	=> [
						'default'			=> null,
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					],
					'favorite' => [
						'default'			=> null,
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					]
				]
			]
		);	
		
		// update article status
		register_rest_route(
			self::NAMESPACE,
			'/add-article',
			[
				'callback'				=> [ $api, 'add_article' ],
				'methods'				=> WP_REST_Server::EDITABLE,
				'permission_callback'	=> [ $this, 'check_auth' ],
				'args' 					=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback' => [ 'Vril_Utility', 'sanitize_var' ],
					],
					'url'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback' => 'esc_url_raw',
					],
					'tags'	=> [
						'default'			=> null,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $this, 'clean_tags' ],
					],
					'read'	=> [
						'default'			=> false,
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					],
					'favorite' => [
						'default'			=> false,
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					]
				]
			]
		);			
	}


	/**
	 * Clean up  tags for new articles
	 * 
	 * @internal 		We'll handle actually validating tags in New_Article class
	 *
	 * @param	mixed	$tags 	Tags
	 * @return 	array 			Array of tag IDs
	 */
	public function clean_tags( $tags = '' )
	{
		$tags = Vril_Utility::convert_to_array( $tags );
		
		return array_unique( array_values( $tags ) );
	}


	/**
	 * Fixes issues with empty access-control-allow-origin headers being sent to browser extension requests
	 * 
	 * @see rest_send_cors_headers
	 *
	 * @param	string	$origin 	Http origin
	 * @return 	mixed 				If request is from Chrome extension, return false 
	 */
	public function extension_origin_fix( $origin )
	{
		if( false !== strpos( $origin, 'chrome-extension' ) ) {
			header( 'x-wp-loa-origin: ' . $origin );
			header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
			header( 'Access-Control-Allow-Credentials: true' );
			header( 'Vary: Origin', false );
						
			$origin = false;
		} 

		return $origin;
	}

}
