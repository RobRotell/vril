<?php


namespace Loa\Controller;


defined( 'ABSPATH' ) || exit;


class API
{
	const NAMESPACE = 'loa/v3';

	private $endpoints = [];


	public function __construct()
	{
		$this->add_wp_hooks();
		$this->load_endpoints();
		$this->create_endpoints();
	}


	/**
	 * Hooks into WordPress
	 *
	 * @return 	void
	 */
	private function add_wp_hooks()
	{
		add_filter( 'vril_whitelist_rest_route', [ $this, 'whitelist_endpoints'], 10, 2 );
		add_filter( 'http_origin', [ $this, 'extension_origin_fix' ] );
	}	


	/**
	 * Load includes for endpoints
	 *
	 * @return 	void
	 */
	private function load_endpoints(): void
	{
		require_once( Loa()::$plugin_path_inc . '/endpoints/class-get-tags.php' );

		require_once( Loa()::$plugin_path_inc . '/endpoints/class-get-articles.php' );
		require_once( Loa()::$plugin_path_inc . '/endpoints/class-add-article.php' );
		require_once( Loa()::$plugin_path_inc . '/endpoints/class-update-article.php' );

		require_once( Loa()::$plugin_path_inc . '/endpoints/class-validate-auth-token.php' );
		require_once( Loa()::$plugin_path_inc . '/endpoints/class-create-auth-token.php' );

		require_once( Loa()::$plugin_path_inc . '/endpoints/class-get-meta.php' );
	}


	/**
	 * Create instances for endpoints
	 *
	 * @return 	void
	 */
	private function create_endpoints(): void
	{
		$this->endpoints['get-tags']			= new \Loa\Endpoints\Get_Tags();
		
		$this->endpoints['get-articles']		= new \Loa\Endpoints\Get_Articles();
		$this->endpoints['add-article']			= new \Loa\Endpoints\Add_Article();
		$this->endpoints['update-article']		= new \Loa\Endpoints\Update_Article();
		
		$this->endpoints['validate-auth-token'] = new \Loa\Endpoints\Validate_Auth_Token();
		$this->endpoints['create-auth-token'] 	= new \Loa\Endpoints\Create_Auth_Token();

		$this->endpoints['get-meta'] 			= new \Loa\Endpoints\Get_Meta();
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
	 * Get specific endpoint
	 *
	 * @param 	string	$endpoint 	Endpoint name
	 * @return 	mixed				Endpoint class or null
	 */
	public function get_endpoint( string $endpoint )
	{
		if( isset( $this->endpoints[ $endpoint ] ) ) {
			return $this->endpoints[ $endpoint ];
		}

		return false;
	}		


	/**
	 * Fixes issues with empty access-control-allow-origin headers being sent to browser extension requests
	 * 
	 * @see rest_send_cors_headers
	 *
	 * @param	string	$origin 	Http origin
	 * @return 	mixed 				If request is from Chrome extension, return false 
	 */
	public static function extension_origin_fix( $origin )
	{
		header( 'x-vril-api: loa' );

		if( false !== strpos( $origin, 'chrome-extension' ) ) {
			header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
			header( 'Access-Control-Allow-Credentials: true' );
			header( 'Vary: Origin', false );
						
			$origin = false;
		} 

		return $origin;
	}


	/**
	 * Confirms user is authorized
	 * 
	 *
	 * @param	string	$username 		Username
	 * @param 	string 	$app_password 	Application password
	 * 
	 * @return 	bool 					True, if authorized
	 */
	public static function user_is_authorized( string $username, string $app_password ): bool
	{
		$result = wp_authenticate_application_password( null, $username, $app_password );

		return ( is_a( $result, 'WP_User' ) && 0 !== $result->get( 'id' ) );
	}	


	/**
	 * Whitelist endpoints
	 *
	 * @param	bool 	$status 	True, if route is whitelisted
	 * @param 	string 	$route 		Route name
	 * 
	 * @return 	bool 				True, if route is whitelisted
	 */
	public function whitelist_endpoints( bool $status, string $route ): bool
	{
		if( str_contains( $route, self::NAMESPACE ) ) {
			$status = true;
		}

		return $status;
	}

}
