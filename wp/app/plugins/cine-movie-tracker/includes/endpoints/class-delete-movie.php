<?php


namespace Cine\Endpoints;


use Cine\Controllers\REST_API;
use Cine\Core\Post_Types;
use Cine\Controllers\Helpers;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


defined( 'ABSPATH' ) || exit;


final class Delete_Movie extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'movies/(?P<id>[\d]+)';
	public string $method	= WP_REST_Server::DELETABLE;


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool 	True, if user can edit posts
	 */
	public function check_permission( WP_REST_Request $request ): bool
	{
		// username and app password should be passed via Authorization header
		$current_user = wp_get_current_user();

		return $current_user->has_cap( 'edit_posts' );
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
		$res = $this->create_response_obj( 'deleted' );
		
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
				);
			}

			$res->add_data( 'deleted', true );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage(), $e->getCode() );
		}

		return rest_ensure_response( $res->package() );			
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
