<?php


namespace Vril\Core_Classes;


use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


defined( 'ABSPATH' ) || exit;


class REST_API_Response
{
	public ?int $start 					= null;
	public ?int $end 					= null;
	public ?int $duration				= null;

	public bool $success 				= false;
	public bool $error 					= false;
	public bool $fulfilled 				= false;

	public ?int $status_code			= null;
	public ?WP_REST_Response $response	= null;

	public array $data 					= [];


	public function __construct()
	{
		$this->start 	= hrtime( true );
		$this->response = new WP_REST_Response();
	}


	/**
	 * Set error state for response object
	 *
	 * @param	string	$err 			Error message
	 * @param 	mixed 	$response_code 	HTTP response code
	 * 
	 * @return	self 					Response object
	 */
	public function set_error( string $err, $response_code = 0 ): self
	{
		$this->error	= true;
		$this->success	= false;

		// clear out any preexisting data
		$this->data = [];

		$response_code = absint( $response_code );
		if( empty( $response_code ) ) {
			$response_code = 200;
		}

		$this
			->add_data_key( 'error' )
			->add_data( 'error', $err )
			->set_status_code( $response_code ); // HTTP response status

		return $this;
	}


	/**
	 * Set status code
	 *
	 * @param	int 	$code 	HTTP status code
	 * @return 	self 			Response object
	 */
	public function set_status_code( int $code = 200 ): self
	{
		$this->status_code = $code;

		return $this;
	}


	/**
	 * Add key to internal data array. 
	 * 
	 * If key already exists, all data associated with key is removed.
	 *
	 * @param	string	$key 	Key for data array
	 * @return 	self 			Response object
	 */
	public function add_data_key( string $key ): self
	{
		$this->data[ $key ] = null;

		return $this;
	}


	/**
	 * Reset data for existing key in data array
	 *
	 * @param	string	$key 	Key for data array
	 * @return 	self 			Response object
	 */
	public function reset_data_key( string $key ): self
	{
		$this->add_data_key( $key );

		return $this;
	}	


	/**
	 * Add data to internal data array by key
	 *
	 * @param	mixed 	$data 	Data
	 * @param 	string 	$key 	Key for data array	
	 * 
	 * @return 	self 			Response object
	 */
	public function add_data( string $key, $data ): self
	{
		if( !isset( $this->data[ $key ] ) ) {
			$this->add_data_key( $key );
		}

		$this->data[ $key ] = $data;

		return $this;
	}


	/**
	 * Package response for sending back to client
	 *
	 * @return 	WP_REST_Response 	WordPress response object
	 */
	public function package(): WP_REST_Response
	{
		$this->end 			= hrtime( true );
		$this->fulfilled	= true;

		if( !$this->error ) {
			$this->status_code	= 200;
			$this->success		= true;
		}

		$response = $this->response;
		$response->set_status( $this->status_code );
		$response->set_data( $this->get_packaged_data() );

		return $response;
	}


	/**
	 * Get packaged data for sending back to client
	 *
	 * @return 	array 	Packaged data
	 */
	public function get_packaged_data(): array
	{
		$this->duration = ( $this->end - $this->start );

		$packaged = [
			'success'	=> $this->success,
			'data'		=> $this->data,
		];

		if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$packaged['debug'] = [
				'duration'		=> $this->duration,
				'memory_usage' 	=> memory_get_usage( true ),
			];
		}

		$this->fulfilled = true;		

		return $packaged;
	}

}
