<?php


namespace Vril\Core_Classes;


use Vril\Core_Classes\REST_API_Response;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;



defined( 'ABSPATH' ) || exit;


abstract class REST_API_Endpoint extends WP_REST_Controller
{
	// protected string $namespace; // inherited from WP_REST_Controller
	public string $route;
	public string $method;


	/**
	 * Creates endpoint
	 *
	 * @return 	void
	 */
	public function __construct()
	{
		$this->add_wp_hooks();
	}


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool|WP_Error 	True, if permitted; otherwise false or error
	 */
	abstract public function check_permission( WP_REST_Request $request );


	/**
	 * Establish endpoint arguments
	 *
	 * @return 	array 	Array of endpoint args
	 */
	abstract public function get_route_args(): array;


	/**
	 * Handle permission check
	 *
	 * @param	WP_REST_Request 	$req	API request
	 * @return 	WP_REST_Response|WP_Error
	 */
	abstract public function handle_request( WP_REST_Request $req );


	/**
	 * Create standardized response object
	 *
	 * @param	string	$keys		Data keys
	 * @return	REST_API_Response	Custom API response object
	 */
	public function create_response_obj( string ...$keys ): REST_API_Response
	{
		$response = new REST_API_Response();

		foreach( $keys as $key ) {
			$response->add_data_key( $key );
		}

		return $response;
	}


	/**
	 * Get API route
	 *
	 * @return 	string 	Route
	 */
	public function get_route()
	{
		return $this->route;
	}


	/**
	 * Get API namespace
	 *
	 * @return 	string 	Namespace
	 */
	public function get_namespace(): string
	{
		return $this->namespace;
	}


	/**
	 * Get endpoint's method
	 *
	 * @return 	string 	Method
	 */
	public function get_method(): string
	{
		return $this->method;
	}		


	/**
	 * Get endpoint URL
	 *
	 * @return 	string 	Endpoint URL
	 */
	public function get_endpoint_url()
	{
		$path = sprintf( '%s/%s', $this->get_namespace(), $this->get_route() );

		return get_rest_url( null, $path );
	}


	/**
	 * Hooks into WordPress
	 *
	 * @return 	void
	 */
	public function add_wp_hooks()
	{
		add_action( 
			'rest_api_init', 
			[ $this, 'register_route' ] 
		);
	}


	/**
	 * Registers API route
	 *
	 * @return 	void
	 */
	public function register_route() 
	{
		register_rest_route(
			$this->get_namespace(),
			$this->get_route(),
			[
				'methods'				=> $this->get_method(),
				'args'					=> $this->get_route_args(),
				'permission_callback'	=> [ $this, 'check_permission' ],
				'callback' 				=> [ $this, 'handle_request' ],
			]
		);
	}

}
