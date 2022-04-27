<?php


namespace Loa\Controller;


use WP_Application_Passwords;
use WP_Error;
use WP_User;


defined( 'ABSPATH' ) || exit;


class Auth_Tokens
{
	private const APP_NAME = 'loa_v2';


	/**
	 * Get hashed auth token for specific user
	 *
	 * @param	int 	$user_id 	User ID
	 * @return	string|null 		String, if auth token exists
	 */
	public static function get_hashed_auth_token( int $user_id ): string|null
	{
		$app_passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );
		foreach( $app_passwords as $app_password ) {
			if( self::APP_NAME === $app_password['name'] ) {
				return $app_password['password'];
			}
		}

		return null;
	}


	/**
	 * Delete preexisting auth token for specific user
	 *
	 * @param	int 	$user_id 	User ID
	 * @return	bool 				Always true
	 */
	public static function delete_auth_token( int $user_id ): bool
	{
		$app_uuid = null;

		$app_passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );
		foreach( $app_passwords as $app_password ) {

			// should only be one
			if( self::APP_NAME === $app_password['name'] ) {
				$app_uuid = $app_password['uuid'];
				break;
			}
		}

		if( $app_uuid ) {
			WP_Application_Passwords::delete_application_password( $user_id, $app_uuid );
		}

		return true;
	}


	/**
	 * Create auth token for specific user
	 *
	 * @param	int 	$user_id 	User ID
	 * @return	bool 				Always true
	 * @throws 	string|WP_Error 	If successful, auth token; otherwise, WP_Error
	 */
	public static function create_auth_token( int $user_id ): string|WP_Error
	{
		$app_args = [
			'name' => self::APP_NAME
		];

		$data = WP_Application_Passwords::create_new_application_password( $user_id, $app_args );
		
		// did it work?
		if( is_wp_error( $data ) ) {
			return $data;
		} else {
			return $data[0];
		}
	}	


	/**
	 * Validates provided auth token for specific user
	 *
	 * @param	WP_User $user	User
	 * @param 	string 	$token 	Token to validate
	 * 
	 * @return	bool 			True, if validated; otherwise, false
	 */
	public static function validate_auth_token( WP_User $user, string $token ): bool
	{
		$user_id 	= $user->get( 'id' );
		$user_login = $user->get( 'user_login' );
		
		// bail early if auth token isn't registered for user
		if( !WP_Application_Passwords::application_name_exists_for_user( $user_id, self::APP_NAME ) ) {
			return false;
		}

		// if auth token is valid, user should be returned
		$validated_user = wp_authenticate_application_password( null, $user_login, $token );

		return ( is_a( $validated_user, 'WP_User' ) && $user_id === $validated_user->get( 'id' ) );
	}		

}
