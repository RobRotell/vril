<?php


namespace Loa\Abstracts;


use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Request;

use Loa\Controller\API as API;
use Loa\Model\API_Response as API_Response;



defined( 'ABSPATH' ) || exit;


abstract class Endpoint extends WP_REST_Controller
{
	public $route;


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
	 * Hooks into WordPress
	 *
	 * @return 	void
	 */
	private function add_wp_hooks()
	{
		add_action( 'rest_api_init', [ $this, 'register_route' ], 9999 );
	}


	/**
	 * Registers API route
	 *
	 * @return 	void
	 */
	abstract public function register_route();


	/**
	 * Handle endpoint request
	 *
	 * @param	WP_REST_Request 	$req	API request
	 * @return 	WP_REST_Response			API response
	 */
	abstract public function handle_request( WP_REST_Request $req ): WP_REST_Response;	


	/**
	 * Get API route
	 *
	 * @return 	string 	Route
	 */
	public function get_route()
	{
		return sprintf( '/%s', $this->route );
	}


	/**
	 * Create standardized response object
	 *
	 * @param	string			$keys 	Data keys
	 * @return	API_Response 			Custom API response object
	 */
	public function create_response_obj( string ...$keys ): API_Response
	{
		$response = new API_Response();

		foreach( $keys as $key ) {
			$response->add_data_key( $key );
		}

		return $response;
	}


	/**
	 * Get endpoint URL
	 *
	 * @return 	string 	Endpoint URL
	 */
	public function get_endpoint_url()
	{
		$path = sprintf( '%s/%s', API::NAMESPACE, $this->route );

		return get_rest_url( null, 'bob' );
	}

}
