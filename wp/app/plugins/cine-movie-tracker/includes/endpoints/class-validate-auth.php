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


final class Validate_Auth extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'auth/validate';
	public string $method	= WP_REST_Server::CREATABLE;


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool 	True, if user can edit posts
	 */
	public function check_permission( WP_REST_Request $request ): bool
	{
		$username 	= $request->get_param( 'username' );
		$user_id 	= username_exists( $username );

		if( $user_id && 0 !== $user_id ) {
			return true;
		} else {
			return new WP_Error(
				'cine/endpoint/validate_auth/invalid_username',
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
			'username' => [
				'required'			=> true,
				'type'				=> 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'auth' => [
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
		$auth_token	= $req->get_param( 'auth' );

		// prep response object
		$res = $this->create_response_obj( 'valid' );

		try {
			$user = get_user_by( 'login', $username );
			
			// not necessarily redundant
			if( !$user ) {
				throw new Exception( sprintf( 'Invalid user: "%s"', $username ), 401 );
			}
			$is_valid = Cine()->auth->validate_auth_token( $user, $auth_token );

			$res->add_data( 'valid', $is_valid );
						
		} catch( Throwable $e ) {
			$res->set_error( 
				$e->getMessage(), 
				$e->getCode() 
			);
		}

		return rest_ensure_response( $res->package() );			
	}

}
