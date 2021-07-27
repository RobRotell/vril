<?php


namespace Loa\Controller;


use Exception;
use Vril_Utility;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


defined( 'ABSPATH' ) || exit;


class Endpoint
{
	const NAMESPACE = 'loa/v3';

	private static $transient_all 	= 'loa_cached_everything';
	private static $token_prefix 	= 'loa_token';
	
	private $auth_key;



	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
		add_action( 'rest_api_init', 	[ $this, 'register_routes' ] );
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
	 * @return	bool 					True, if authorized
	 */
	public static function check_auth( WP_REST_Request $request ): bool
	{
		$auth = $request->get_param( 'auth' );

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

		$permission_callback_public	= '__return_true';
		$permission_callback_auth	= [ $this, 'check_auth' ];

		// grab subset of articles
		register_rest_route(
			self::NAMESPACE,
			'/get-articles',
			[
				'methods'				=> WP_REST_Server::READABLE,
				'callback'				=> [ $api, 'get_articles' ],
				'permission_callback'	=> $permission_callback_public,
				'args'		=> [
					'page'	=> [
						'default'			=> 1,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ],
					],
					'count'	=> [
						'default'			=> 50,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ],
					],
					'tag'	=> [
						'default'			=> 0,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ], // @todo — could be array
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
					'no_cache' => [
						'default'			=> false,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					]
				]
			]
		);



		// $helper = Loa()->helper;

		// register_rest_route(
		// 	$namespace,
		// 	'/get-auth-token',
		// 	[
		// 		'methods'	=> WP_REST_Server::EDITABLE,
		// 		'callback'	=> [ $this, 'route_get_auth_token' ],
		// 		'permission_callback' => '__return_true',
		// 		'args'		=> [
		// 			'auth_key' => [
		// 				'required'			=> true,
		// 				'sanitize_callback'	=> [ __NAMESPACE__ . '\Helpers', 'sanitize_auth_key' ],
		// 				'type'				=> 'string',
		// 				// 'validate_callback'	=> [ $this, 'validate_auth_key' ]
		// 			]
		// 		]
		// 	]
		// );

		// register_rest_route(
		// 	$namespace,
		// 	'/check-auth-token',
		// 	[
		// 		'methods'	=> WP_REST_Server::READABLE,
		// 		'callback'	=> [ $this, 'route_check_auth_token' ],
		// 		'permission_callback' => '__return_true',
		// 		'args'		=> [
		// 			'token'	=> [
		// 				'required'	=> true,
		// 				'type'		=> 'string',
		// 			]
		// 		]
		// 	]
		// );	
		
		// register_rest_route(
		// 	$namespace,
		// 	'/delete-auth-tokens',
		// 	[
		// 		'methods'	=> WP_REST_Server::DELETABLE,
		// 		'callback'	=> [ $this, 'route_delete_auth_tokens' ],
		// 		'permission_callback' => '__return_true',
		// 		'args'		=> [
		// 			'auth_key' => [
		// 				'required'			=> true,
		// 				'sanitize_callback'	=> [ __NAMESPACE__ . '\Helpers', 'sanitize_auth_key' ],
		// 				'type'				=> 'string',
		// 			]
		// 		]
		// 	]
		// );		
		
		// register_rest_route(
		// 	$namespace,
		// 	'/get-everything',
		// 	[
		// 		'methods'	=> WP_REST_Server::READABLE,
		// 		'callback'	=> [ $this, 'route_get_everything' ],
		// 		'permission_callback' => '__return_true',
		// 		'args'		=> [
		// 			'token'	=> [
		// 				'required'			=> true,
		// 				'type'				=> 'string',
		// 				'validate_callback'	=> [ $this, 'validate_token' ]
		// 			]
		// 		]
		// 	]
		// );
		
		// register_rest_route(
		// 	$namespace,
		// 	'/get-tags',
		// 	[
		// 		'methods'	=> WP_REST_Server::READABLE,
		// 		'callback'	=> [ $this, 'route_get_tags' ],
		// 		'permission_callback' => '__return_true',
		// 		'args'		=> [
		// 			'token'	=> [
		// 				'required'			=> true,
		// 				'type'				=> 'string',
		// 				'validate_callback'	=> [ $this, 'validate_token' ]
		// 			]
		// 		]
		// 	]
		// );
		
		// register_rest_route(
		// 	$namespace,
		// 	'/get-articles',
		// 	[
		// 		'methods'	=> WP_REST_Server::READABLE,
		// 		'callback'	=> [ $this, 'route_get_articles' ],
		// 		'permission_callback' => '__return_true',
		// 		'args'		=> [
		// 			'token'	=> [
		// 				'required'			=> true,
		// 				'type'				=> 'string',
		// 				'validate_callback'	=> [ $this, 'validate_token' ]
		// 			]
		// 		]
		// 	]
		// );
		
		// register_rest_route(
		// 	$namespace,
		// 	'/(add|update)-article',
		// 	[
		// 		'callback'				=> [ $this, 'route_add_article' ],
		// 		'methods'				=> WP_REST_Server::EDITABLE,
		// 		'permission_callback'	=> '__return_true',
		// 		'args'					=> [
		// 			'token'	=> [
		// 				'required'			=> true,
		// 				'type'				=> 'string',
		// 				'validate_callback'	=> [ $this, 'validate_token' ]
		// 			],
		// 			'url'	=> [
		// 				'required'			=> true,
		// 				'sanitize_callback'	=> [ __NAMESPACE__ . '\Helpers', 'sanitize_url' ],
		// 				'type'				=> 'string',
		// 			],
		// 			'tags'	=> [
		// 				'default'			=> '',
		// 				'required' 			=> false,
		// 				'type'				=> [ 'string', 'array' ],
		// 			],
		// 			'read'	=> [
		// 				'default'			=> false,
		// 				'required'			=> false,
		// 				'type'				=> 'boolean'
		// 			],
		// 			'favorite' => [
		// 				'default'			=> false,
		// 				'required'			=> false,
		// 				'type'				=> 'boolean'
		// 			]
		// 		]
		// 	]
		// );							
	}





	/**
	 * Get authorization token based on supplied key
	 *
	 * @param	WP_REST_Request	$request 	Request
	 * @return 	WP_REST_Response
	 */
	public function route_get_auth_token( WP_REST_Request $request )
	{
		// prep response object
		$response = new Response();

		$key = $request->get_param( 'auth_key' );

		// does submitted key match auth key?
		if( !$this->validate_auth_key( $key ) ) {
			$response->add_error( 'Invalid authorization key' );

		// create token
		} else {
			$token = wp_generate_password( 32, true );
	
			// save token to database (valid for seven days)
			$success = set_transient( 
				sprintf( '%s_%s', self::$token_prefix, $token ), 
				$key, 
				604800 
			);
	
			if( !$success ) {
				$response->add_error( 'Failed to generate token' );
			} else {
				$response->add_success( $token, 'token' );
			}
		}

		return rest_ensure_response( $response );
	}


	/**
	 * Check if provided authorization token is a valid token
	 *
	 * @param	WP_REST_Request	$request 	Request
	 * @return 	WP_REST_Response
	 */
	public function route_check_auth_token( WP_REST_Request $request )
	{
		// prep response object
		$response = new Response( $request );

		$token = $request->get_param( 'token' );
		$is_valid = $this->validate_token( $token );

		if( !$is_valid ) {
			$response->add_error( 'Invalid token' );
		} else {
			$response->add_success( true, 'valid' );
		}

		return rest_ensure_response( $response );
	}


	/**
	 * Delete all existing authorization tokens
	 *
	 * @param	WP_REST_Request	$request 	Request
	 * @return 	WP_REST_Response
	 */
	public function route_delete_auth_tokens( WP_REST_Request $request )
	{
		global $wpdb;

		// prep response object
		$response = new Response();

		$key = $request->get_param( 'auth_key' );		
		if( !$this->validate_auth_key( $key ) ) {
			$response->add_error( 'Invalid authorization key' );

		} else {
			$prefix = sprintf( '_transient_%s', self::$token_prefix );
			$sql = $wpdb->prepare(
				"SELECT option_name AS name from $wpdb->options WHERE option_name LIKE %s",
				'%' . $wpdb->esc_like( $prefix ) . '%'
			);
	
			$transients = $wpdb->get_results( $sql, ARRAY_N );
			foreach( $transients as $transient ) {
				$transient = str_replace( '_transient_', '', $transient[0] );
				delete_transient( $transient );
			}
		}

		return rest_ensure_response( $response );
	}		


	/**
	 * Get all articles and all article tags
	 *
	 * @param	WP_REST_Request	$request 	Request
	 * @return 	WP_REST_Response
	 */
	public function route_get_everything( WP_REST_Request $request )
	{
		// prep response object
		$response = new Response( $request );
		
		$data = get_transient( self::$transient_all );
		
		// confirm valid data
		$tags 		= $data['tags'] ?? '';
		$articles 	= $data['articles'] ?? '';
		$total_read = $data['totalRead'] ?? '';

		// if we're missing anything, refetch
		if( empty( $tags ) || empty( $articles ) || empty( $total_read ) ) {
			$tags 		= Helpers::get_tags();
			$articles 	= Helpers::get_articles();
			$total_read = Helpers::get_total_read();

			set_transient( 
				self::$transient_all, 
				[
					'tags'	=> $tags,
					'articles'	=> $articles,
					'totalRead'	=> $total_read
				],
				DAY_IN_SECONDS
			);
		}
		
		$response->add_data( 'tags', $tags );
		$response->add_data( 'articles', $articles );
		$response->add_data( 'totalRead', $total_read );

		return rest_ensure_response( $response->add_success() );
	}


	/**
	 * Get all article tags
	 *
	 * @param	WP_REST_Request	$request 	Request
	 * @return 	WP_REST_Response
	 */
	public function route_get_tags( WP_REST_Request $request )
	{
		// prep response object
		$response = new Response( $request );
		
		$response->add_data( 'tags', Helpers::get_tags() );

		return rest_ensure_response( $response->add_success() );
	}	


	/**
	 * Get all articles
	 *
	 * @param	WP_REST_Request	$request 	Request
	 * @return 	WP_REST_Response
	 */
	public function route_get_articles( WP_REST_Request $request )
	{
		// prep response object
		$response = new Response( $request );
		
		$response->add_data( 'articles', Helpers::get_articles() );

		return rest_ensure_response( $response->add_success() );
	}


	/**
	 * Add a new article by URL
	 *
	 * @param	WP_REST_Request	$request 	Request
	 * @return 	WP_REST_Response
	 */
	public function route_add_article( WP_REST_Request $request )
	{
		// prep response object
		$response = new Response( $request );

		$url 		= $request->get_param( 'url' );
		$tags 		= $request->get_param( 'tags' );
		$read 		= $request->get_param( 'read' );
		$favorite 	= $request->get_param( 'favorite' );

		$article_id = Helpers::add_article( $url, $tags, $read, $favorite );
		if( !empty( $article_id ) && is_int( $article_id ) ) {
			$response->add_success( $article_id, 'articleId' );
		} else {
			$response->add_error( 'Failed to add article' );
		}

		return rest_ensure_response( $response );
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


	/**
	 * Validate a request's token
	 *
	 * @param	string	$token 	Request token
	 * @return	bool 			True, if valid
	 */
	public function validate_token( string $token )
	{
		$token = sanitize_text_field( $token );
		$token = trim( $token );

		$transient = get_transient( 
			sprintf( 
				'%s_%s', 
				self::$token_prefix, 
				$token 
			) 
		);

		// transient value should match authorization key
		return $this->validate_auth_key( $transient );
	}


	/**
	 * Validate a request's authentication key
	 *
	 * @param	mixed	$key 	Request authentication key
	 * @return	bool 			True, if valid
	 */
	public function validate_auth_key( $key = '' )
	{
		return $key === $this->auth_key;
	}	

}