<?php

namespace Loa_Article_Tracker;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;


defined( 'ABSPATH' ) || exit;


class Response extends WP_REST_Response
{
	public $data = [
		'error'		=> false,
		'success'	=> false,
		'message'	=> null,
		'__params'	=> null
	];


	/**
	 * Basic class to normalize endpoint responses
	 *
	 * @param 	WP_REST_Request 	$request 	Optional; original request
	 * @return 	self
	 */
	public function __construct( $arg = null )
	{
		if( $arg instanceof WP_REST_Request ) {
			$params = $arg->get_params();

			// remove confidentialish info
			unset( $params['auth_key'] );
			unset( $params['token'] );

			if( !empty( $params ) ) {
				$this->data['__params'] = $params;
			}
		}

		return $this;
	}


	/**
	 * Add data by key/value
	 *
	 * @param	string	$key 	Data key
	 * @param 	mixed 	$value 	Data value
	 * 
	 * @return 	self
	 */
	public function add_data( string $key = 'message', $value = null )
	{
		if( isset( $this->data[ $key ] ) && !empty( $this->data[ $key ] ) ) {
			throw new Exception( 'Data value already set' );
		}

		$this->data[ $key ] = $value;

		return $this;
	}


	/**
	 * Add (and signal) error
	 *
	 * @param 	string 	$value 	Data value
	 * @param	string	$key 	Data key
	 * 
	 * @return	self 
	 */
	public function add_error( string $value, string $key = 'message' )
	{
		if( $this->data['success'] ) {
			throw new Exception( 'Response already set as success' );
		}
		$this->data['error'] = true;

		$this->add_data( $key, $value );

		return $this;
	}


	/**
	 * Add (and signal) success
	 *
	 * @param 	string 	$value 	Data value
	 * @param	string	$key 	Data key
	 */
	public function add_success( string $value = '', string $key = 'message' )
	{
		if( $this->data['error'] ) {
			throw new Exception( 'Response already set as error' );
		}
		$this->data['success'] = true;

		$this->add_data( $key, $value );

		return $this;
	}


}