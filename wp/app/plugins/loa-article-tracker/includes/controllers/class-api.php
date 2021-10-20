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
	}


	/**
	 * Hooks into WordPress
	 *
	 * @return 	void
	 */
	private function add_wp_hooks()
	{
		add_filter( 'http_origin', [ $this, 'extension_origin_fix' ] );
	}	


	/**
	 * Check if current request is a REST request
	 *
	 * @return 	void
	 */
	public function load_endpoints()
	{
		require_once( Loa()::$plugin_path_inc . '/endpoints/class-get-articles.php' );
		require_once( Loa()::$plugin_path_inc . '/endpoints/class-add-article.php' );
		require_once( Loa()::$plugin_path_inc . '/endpoints/class-get-tags.php' );
		require_once( Loa()::$plugin_path_inc . '/endpoints/class-update-article.php' );

		$this->endpoints['get-articles'] 	= new \Loa\Endpoints\Get_Articles();
		$this->endpoints['add-article'] 	= new \Loa\Endpoints\Add_Article();
		$this->endpoints['get-tags'] 		= new \Loa\Endpoints\Get_Tags();
		$this->endpoints['update-article'] 	= new \Loa\Endpoints\Update_Article();
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
	 * @see rest_send_cors_headers
	 *
	 * @param	string	$origin 	Http origin
	 * @return 	mixed 				If request is from Chrome extension, return false 
	 */
	public static function check_auth( $origin )
	{
		return true;

		header( 'x-vril-api: loa' );

		if( false !== strpos( $origin, 'chrome-extension' ) ) {
			header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
			header( 'Access-Control-Allow-Credentials: true' );
			header( 'Vary: Origin', false );
						
			$origin = false;
		} 

		return $origin;
	}	

}
