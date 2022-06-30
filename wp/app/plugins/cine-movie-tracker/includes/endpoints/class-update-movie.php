<?php


namespace Cine\Endpoints;


use Cine\Controllers\Helpers;
use Cine\Controllers\REST_API;
use Cine\Core\Post_Types;
use Cine\Core\Taxonomies;
use Cine\Core\Transients;
use Cine\Models\Movie_Block;
use WP_Error;
use Exception;
use Throwable;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


defined( 'ABSPATH' ) || exit;


final class Update_Movie extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'movies/(?P<id>[\d]+)';
	public string $method	= WP_REST_Server::EDITABLE;


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool 	True, if user can edit posts
	 */
	public function check_permission( WP_REST_Request $request ): bool
	{
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
			'id' => [
				'required'			=> true,
				'sanitize_callback'	=> 'absint',
				'type'				=> 'string',
				'validate_callback'	=> [ $this, 'validate_id_is_movie' ],
			],

			// todo — currently from query param; need to rethink approach
			'watched' => [
				'default'			=> false,
				'sanitize_callback' => [ 'Vril_Utility', 'convert_to_bool' ],
				'type'				=> 'string',
			]
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
		$id 		= $req->get_param( 'id' );
		$watched	= $req->get_param( 'watched' );

		// prep response object
		$res = $this->create_response_obj( 'updated', 'movie' );
		
		try {
			$post = get_post( $id );

			// redundant, as handled in validate callback, but nice to have
			if( !Helpers::assert_post_is_movie( $post ) ) {
				throw new Exception(
					sprintf( 'Invalid movie ID: "%s"', $id ),
					400
				);
			}

			$updated = update_field( 'to_watch', !$watched, $id );

			if( !$updated ) {
				throw new Exception(
					sprintf( 
						'Failed to update watch status for "%s" as %s',
						$post->post_title,
						( $watched ) ? 'watched' : 'waiting to be watched'
					)
				);
			}

			$movie = new Movie_Block( $id );
			$movie = get_object_vars( $movie );

			$res
				->add_data( 'updated', true )
				->add_data( 'movie', $movie );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
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
