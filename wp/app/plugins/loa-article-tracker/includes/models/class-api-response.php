<?php


namespace Loa\Model;


use Exception;
use Vril_Utility;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;


defined( 'ABSPATH' ) || exit;


class Api_Response
{
	private $start 			= null;
	private $end 			= null;
	private $duration		= null;

	private $success 		= false;
	private $error 			= false;
	private $fulfilled		= false;

	private $status_code	= null;
	private $response 		= null;

	private $data 			= [];


	public function __construct()
	{
		$this->start 	= hrtime( true );
		$this->response = new WP_REST_Response();
	}


	/**
	 * Set error state for response object
	 *
	 * @param	string	$err 			Error message
	 * @param 	int 	$response_code 	HTTP response code
	 * @return	self 					Response object
	 */
	public function set_error( string $err, int $response_code = 500 ): self
	{
		$this->error	= true;
		$this->success	= false;

		// clear out any preexisting data
		$this->data = [];

		$this
			->add_data_key( 'error' )
			->add_data( $err, 'error' )
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
		$this->data[ $key ] = [];

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
	public function add_data( $data, string $key ): self
	{
		if( !isset( $this->data[ $key ] ) ) {
			$this->add_data_key( $key );
		}

		$this->data[ $key ][] = $data;

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
			$this->success = true;
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
	private function get_packaged_data(): array
	{
		$packaged = [
			'success'	=> $this->success,
			'data'		=> $this->data,
		];

		if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$packaged['duration'] = ( $this->end - $this->start );
		}

		return $packaged;
	}

}
