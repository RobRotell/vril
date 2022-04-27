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


class Validate_Auth_Token extends \Loa\Abstracts\Endpoint
{
	public $route = 'validate-auth-token';


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
					'auth_token'	=> [
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
		$auth_token	= $req->get_param( 'auth_token' );

		// prep response object
		$res = $this->create_response_obj( 'valid' );

		try {
			$user = get_user_by( 'login', $username );
			
			if( !$user ) {
				throw new Exception( 'Invalid user' );
			}
			$is_valid = Auth_Tokens::validate_auth_token( $user, $auth_token );

			$res->add_data( 'valid', $is_valid );
						
		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );			
	}

}
