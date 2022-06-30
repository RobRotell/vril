<?php


namespace Cine\Endpoints;


use Cine\Controllers\Helpers;
use Cine\Controllers\REST_API;
use Cine\Core\Post_Types;
use Cine\Core\Taxonomies;
use Cine\Core\Transients;
use Cine\Models\Movie_Block;
use WP_Error;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


defined( 'ABSPATH' ) || exit;


final class Get_Movies extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'movies';
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
			'to_watch' => [
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
		$params = $req->get_params();

		// prep response object
		$res = $this->create_response_obj( 'movies', 'meta', 'params' );
		$res->add_data( 'params', $params );
		
		try {

			// data will contain "movies" and "meta" props
			$data = $this->get_from_transients( $params );
			if( empty( $data ) ) {
				$data = $this->get_from_query( $params );
			}

			$res
				->add_data( 'meta', $data['meta'] )
				->add_data( 'movies', $data['movies'] );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );			
	}


	/**
	 * Get movies (and query meta) from transients
	 *
	 * @param 	array 	$params 	Request params
	 * @return 	array|false 		Array of "meta" and "movies" if transient found; otherwise, false
	 */
	private function get_from_transients( array $params ): array|false
	{
		$transient_key 		= sprintf( 'get_movies_%s', http_build_query( $params ) );
		$transient_value 	= Transients::get_transient( $transient_key );

		if( isset( $transient_value['meta'] ) && isset( $transient_value['movies'] ) ) {
			return $transient_value;
		}

		return false;
	}


	/**
	 * Get movies (and query meta) from WP query
	 *
	 * @param 	array 	$params 	Request params
	 * @return	array 				Contains "meta" and "movies" props
	 */
	private function get_from_query( array $params ): array
	{
		$meta	= [];
		$movies = [];

		[
			'count' 	=> $count,
			'genre' 	=> $genre,
			'id' 		=> $id,
			'keyword' 	=> $keyword,
			'page' 		=> $page,
			'to_watch'	=> $to_watch,
		] = $params;

		$query_args = [
			'fields'			=> 'ids',
			'order'				=> 'ASC',
			'orderby'			=> 'title',
			'paged'				=> $page,
			'post_type' 		=> Post_Types::POST_TYPE,
			'posts_per_page' 	=> $count,
		];

		// querying for specific movie?
		if( !empty( $id ) ) {
			$query_args['post__in'] = (array)$id;

		} else {

			// querying for specific category of movies?
			if( !empty( $genre ) ) {
				$query_args['tax_query'] = [
					[
						'field'		=> 'term_id',
						'taxonomy' 	=> Taxonomies::TAXONOMY,
						'terms' 	=> $genre,
					]
				];
			}

			// querying movies by keyword?
			if( !empty( $keyword ) ) {
				$query_args['s'] = $keyword;
			}

			// querying movies by whether they've been watched or not?
			if( $to_watch ) {
				$query_args['meta_query'] = [
					[
						'key' 	=> 'to_watch',
						'value'	=> true,
					]
				];
			}
		}

		$query = new WP_Query( $query_args );
		
		// convert movie posts in movie blocks with specific movie info
		foreach( $query->posts as $post_id ) {
			$movie 		= new Movie_Block( $post_id );
			$movies[] 	= get_object_vars( $movie );
		}

		// used to determine if more pages of posts or if last page (e.g. total / page)
		$meta['post_count']		= $query->post_count;
		$meta['total_posts'] 	= $query->found_posts;

		$data = compact( 'meta', 'movies' );

		// save transient for later queries
		$transient_key = sprintf( 'get_movies_%s', http_build_query( $params ) );
		Transients::set_transient( $transient_key, null, $data );

		return $data;
	}
}
