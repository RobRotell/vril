<?php


namespace Cine\Endpoints;


use Cine\Controllers\REST_API;
use Cine\Controllers\Movies;
use Cine\Models\Frontend_Movie;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Exception;
use Throwable;


defined( 'ABSPATH' ) || exit;


final class Add_Movie extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'movies/(?P<id>[\d]+)';
	public string $method	= WP_REST_Server::CREATABLE;


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
			'id'	=> [
				'required'			=> true,
				'sanitize_callback'	=> 'absint',
				'type'				=> 'string',
			],
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
		// tmdb is valid ID
		// movie already exists
			// maybe update
		// add movie

		$tmdb_id = $req->get_param( 'id' );
		$watched = $req->get_param( 'watched' );

		$res = $this->create_response_obj( 'movie' );
		
		try {
			// movie already exists?
			$movie_post_id = Movies::get_movie_post_id_by_tmdb_id( $tmdb_id );
			if( !$movie_post_id ) {
				$movie_post_id = Movies::create_movie_from_tmdb_id( $tmdb_id );
			}

			$movie = new Frontend_Movie( $movie_post_id );

			$res->add_data( 'movie', $movie );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );			
	}

}
