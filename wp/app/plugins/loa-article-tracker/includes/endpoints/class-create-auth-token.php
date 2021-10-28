<?php


namespace Loa\Endpoints;


use Exception;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


use Loa\Controller\API as API;
use Loa\Controller\Auth_Tokens as Auth_Tokens;
use Loa\Abstracts\Endpoint as Endpoint;


defined( 'ABSPATH' ) || exit;


class Create_Auth_Token extends Endpoint
{
	public $route 	= 'auth-token';
	public $method 	= WP_REST_Server::CREATABLE;


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool|WP_Error 	True, if user exists; otherwise, WP_Error
	 */
	public function check_permission( WP_REST_Request $request ): bool|WP_Error
	{
		$username = $request->get_param( 'username' );
		$user_id = username_exists( $username );

		if( $user_id && 0 !== $user_id ) {
			return true;

		} else {
			return new WP_Error(
				'loa_invalid_user',
				'Invalid username',
				[
					'status' => 401
				]
			);
		}
	}	


	/**
	 * Establish endpoint arguments
	 *
	 * @return 	array 	Args
	 */
	public function get_route_args(): array
	{
		return [
			'username'	=> [
				'required'			=> true,
				'type'				=> 'string',
				'sanitize_callback' => [ 'Vril_Utility', 'sanitize_var' ],
			],
			'password'	=> [
				'required'			=> true,
				'type'				=> 'string',
				'sanitize_callback' => [ 'Vril_Utility', 'sanitize_var' ],
			],
		];
	}


	/**
	 * Handle endpoint request
	 *
	 * @param	WP_Rest_Request 	$req	API request
	 * @return 	WP_REST_Response			API response
	 */
	public function handle_request( WP_Rest_Request $req ): WP_REST_Response
	{
		$username	= $req->get_param( 'username' );
		$password	= $req->get_param( 'password' );

		// prep response object
		$res = $this->create_response_obj( 'auth_token' );

		try {
			// valid user creds?
			$user = wp_authenticate_username_password( null, $username, $password );

			if( is_wp_error( $user ) ) {
				throw new Exception( 'Invalid user credentials', 401 );
			}
			$user_id = absint( $user->get( 'ID' ) );

			// delete preexisting auth token
			Auth_Tokens::delete_auth_token( $user_id );

			// create new auth token
			$token = Auth_Tokens::create_auth_token( $user_id );

			if( is_wp_error( $token ) ) {
				throw new Exception( $token->get_error_message(), $token->get_error_code() );
			} else {
				$res->add_data( 'auth_token', $token );
			}
			
		} catch( Throwable $e ) {
			$res->set_error( 
				$e->getMessage(), 
				$e->getCode() 
			);
		}

		return rest_ensure_response( $res->package() );			
	}

}
