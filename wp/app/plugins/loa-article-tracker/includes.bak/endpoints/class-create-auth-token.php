<?php


namespace Loa\Endpoints;


use Throwable;
use Exception;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


use Loa\Controller\API as API;
use Loa\Controller\Auth_Tokens as Auth_Tokens;


defined( 'ABSPATH' ) || exit;


class Create_Auth_Token extends \Loa\Abstracts\Endpoint
{
	public $route = 'create-auth-token';


	/**
	 * Registers API route
	 *
	 * @return 	void
	 */
	public function register_route()
	{
		register_rest_route(
			API::NAMESPACE,
			$this->get_route(),
			[
				'callback'				=> [ $this, 'handle_request' ],
				'methods'				=> WP_REST_Server::EDITABLE,
				'permission_callback'	=> '__return_true',
				'args' 					=> [
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
				]
			]
		);
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
				throw new Exception( 'Invalid user credentials' );
			}
			$user_id = absint( $user->get( 'ID' ) );

			// delete preexisting auth token
			Auth_Tokens::delete_auth_token( $user_id );

			// create new auth token
			$token = Auth_Tokens::create_auth_token( $user_id );

			if( is_wp_error( $token ) ) {
				throw new Exception( $token->get_error_message() );
			} else {
				$res->add_data( 'auth_token', $token );
			}
			
		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );			
	}

}
