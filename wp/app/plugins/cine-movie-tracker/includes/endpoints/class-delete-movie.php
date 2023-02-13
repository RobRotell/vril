<?php


namespace Cine\Endpoints;


use Cine\Controllers\REST_API;
use Cine\Core\Post_Types;
use Cine\Controllers\Helpers;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Exception;
use Throwable;


defined( 'ABSPATH' ) || exit;


final class Delete_Movie extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'movie/(?P<id>[\d]+)';
	public string $method	= WP_REST_Server::DELETABLE;


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool|WP_Error 	True, if user can edit posts; otherwise, WP_Error
	 */
	public function check_permission( WP_REST_Request $request ): bool|WP_Error
	{
		// username and app password should be passed via Authorization header
		$current_user = wp_get_current_user();

		if( 0 === $current_user->ID ) {
			return new WP_Error(
				'cine/endpoint/delete_movie/invalid_user',
				'Invalid authorization. Check your authorization and try again.',
				[ 
					'status' => 401
				]
			);
		}

		if( !$current_user->has_cap( 'edit_posts' ) ) {
			return new WP_Error(
				'cine/endpoint/delete_movie/invalid_permissions',
				'You are not permitted to delete a movie.',
				[ 
					'status' => 403
				]
			);
		}

		return true;
	}	


	/**
	 * Establish endpoint arguments
	 *
	 * @return 	array 	Args
	 */
	public function get_route_args(): array
	{
		return [

			// right now, only supports one movie ID; might change in future
			'id' => [
				'default'			=> 0,
				'sanitize_callback' => 'absint',
				'type'				=> 'string',
				'validate_callback'	=> [ $this, 'validate_id_is_movie' ],
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
		$id = $req->get_param( 'id' );

		// prep response object
		$res = new WP_REST_Response();		
		
		try {
			$post = get_post( $id );

			// redundant, as handled in validate callback, but nice to have
			if( !Helpers::assert_post_is_movie( $post ) ) {
				throw new Exception(
					sprintf( 'Invalid movie ID: "%s"', $id ),
					400
				);
			}

			$deleted = wp_delete_post( $id );

			if( !$deleted ) {
				throw new Exception(
					sprintf( 'Failed to delete movie: "%s" (ID: "%s")', $post->post_title, $id ),
					500
				);
			}

			$res->set_data(
				[
					'deleted' => true
				]
			);
			$res->set_status( 200 );

		} catch( Throwable $e ) {
			$res->set_data(
				[
					'error' => $e->getMessage()
				]
			);
			$res->set_status( $e->getCode() );
		}

		return rest_ensure_response( $res );			
	}


	/**
	 * Validate that passed ID is a movie post
	 *
	 * @param	int				$id		Passed ID
	 * @param 	WP_REST_Request	$req	REST API request
	 * @param 	string 			$key 	Param name
	 * 
	 * @return 	bool 					True, if valid
	 */
	public function validate_id_is_movie( int $param, WP_REST_Request $req, string $key ): bool
	{
		return Helpers::assert_post_is_movie( $param );
	}

}
