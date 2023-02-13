<?php


namespace Cine\Endpoints;


use Cine\Controllers\REST_API;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Exception;
use Throwable;


defined( 'ABSPATH' ) || exit;


final class Create_Auth extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'auth';
	public string $method	= WP_REST_Server::CREATABLE;


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool 	True, if user can edit posts
	 */
	public function check_permission( WP_REST_Request $request ): bool|WP_Error
	{
		$username = $request->get_param( 'username' );
		$user_id = username_exists( $username );

		if( $user_id && 0 !== $user_id ) {
			return true;
		} else {
			return new WP_Error(
				'cine/endpoint/create_auth/invalid_username',
				sprintf( 'Invalid username: "%s"', $username ),
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
				'sanitize_callback' => 'sanitize_text_field',
			],
			'password'	=> [
				'required'			=> true,
				'type'				=> 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}


	/**
	 * Handle endpoint request
	 *
	 * @param	WP_Rest_Request 	$req	API request
	 * @return 	WP_REST_Response|WP_Error	API response
	 */
	public function handle_request( WP_Rest_Request $req ): WP_REST_Response|WP_Error
	{
		$username	= $req->get_param( 'username' );
		$password	= $req->get_param( 'password' );

		$auth = Cine()->auth;

		// prep response object
		$res = new WP_REST_Response();

		try {
			// valid user creds?
			$user = wp_authenticate_username_password( null, $username, $password );

			if( is_wp_error( $user ) ) {
				throw new Exception( 'Invalid user credentials.', 401 );
			}
			$user_id = absint( $user->get( 'ID' ) );

			// delete preexisting auth token
			$auth->delete_auth_token( $user_id );

			// create new auth token
			$token = $auth->create_auth_token( $user_id );

			if( is_wp_error( $token ) ) {
				throw new Exception( $token->get_error_message(), 500 );
			} 
			
			$res->set_data(
				[
					'auth' => $token
				]
			);
			$res->set_status( 200 );
			
		} catch( Throwable $e ) {
			$res->set_data(
				[
					'error' => $e->getMessage(),
				]
			);
			$res->set_status( $e->getCode() );
		}

		return rest_ensure_response( $res );
	}

}
