<?php


namespace Loa\Endpoints;


use Throwable;
use WP_Application_Passwords;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


use Loa\Controller\API as API;


defined( 'ABSPATH' ) || exit;


class Create_Auth_Token extends \Loa\Abstracts\Endpoint
{
	public $route = 'create-auth-token';

	private const APP_NAME = 'loa_v2';


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
		$username = $req->get_param( 'username' );
		$password = $req->get_param( 'password' );

		// prep response object
		$res = $this->create_response_obj( 'password' );

		try {

			// valid user creds?
			$user = wp_authenticate_username_password( null, $username, $password );

			if( is_wp_error( $user ) ) {
				throw new Exception( 'Invalid credentials' );
			}
			$user_id = $user->get( 'id' );

			// check for preexisting passwords
			$app_passwords = WP_Application_Passwords::get_user_application_passwords( $user_id, self::APP_NAME );
			if( !empty( $app_passwords ) ) {
				$app_uuid = null;

				foreach( $app_passwords as $app_password ) {
					if( self::APP_NAME === $app_password['name'] ) {
						$app_uuid = $app_password['uuid'];
						break;
					}
				}

				// delete preexisting password
				if( $app_uuid ) {
					$deleted = WP_Application_Passwords::delete_application_password( $user_id, $app_uuid );

					if( is_wp_error( $deleted ) ) {
						throw new Exception( $deleted->get_error_message() );
					}
				}
			}

			// now, time to create password
			$password = null;

			$app_args = [
				'name' => self::APP_NAME
			];

			$app_data = WP_Application_Passwords::create_new_application_password( $user_id, $app_args );
			if( is_wp_error( $app_data ) ) {
				throw new Exception( $app_data->get_error_message() );
			}

			$password = $app_data[0];

			$res->add_data( 'password', $password );
			
		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );			
	}

}
