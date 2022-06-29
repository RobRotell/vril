<?php


namespace Cine\Controllers;

use Cine\Controller\Endpoint;
use Cine\Endpoints\Get_Movies as Endpoint_Get_Movies;
use Cine\Endpoints\Query_TMDb as Endpoint_Query_TMDb;


defined( 'ABSPATH' ) || exit;


class REST_API
{
	const NAMESPACE = 'cine/v2';

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
		add_filter( 
			'vril_whitelist_rest_route', 
			[ $this, 'whitelist_endpoints'], 
			10, 2 
		);
		
		add_filter( 
			'http_origin', 
			[ $this, 'extension_origin_fix' ] 
		);
	}	


	/**
	 * Load includes for endpoints
	 *
	 * @return 	void
	 */
	private function load_endpoints(): void
	{
		require_once( Cine()::$plugin_path_inc . '/endpoints/class-get-movies.php' );
		require_once( Cine()::$plugin_path_inc . '/endpoints/class-query-tmdb.php' );

		// require_once( Cine()::$plugin_path_inc . '/endpoints/class-get-articles.php' );
		// require_once( Cine()::$plugin_path_inc . '/endpoints/class-add-article.php' );
		// require_once( Cine()::$plugin_path_inc . '/endpoints/class-update-article.php' );

		// require_once( Cine()::$plugin_path_inc . '/endpoints/class-validate-auth-token.php' );
		// require_once( Loa()::$plugin_path_inc . '/endpoints/class-create-auth-token.php' );
	}


	/**
	 * Create instances for endpoints
	 *
	 * @return 	void
	 */
	private function create_endpoints(): void
	{
		$this->endpoints = [
			'get-movies' => new Endpoint_Get_Movies,
			'query-tmdb' => new Endpoint_Query_TMDb,
		];
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
		header( 'x-vril-api: cine' );

		if( false !== strpos( $origin, 'chrome-extension' ) ) {
			header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
			header( 'Access-Control-Allow-Credentials: true' );
			header( 'Vary: Origin', false );
						
			$origin = false;
		} 

		return $origin;
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
