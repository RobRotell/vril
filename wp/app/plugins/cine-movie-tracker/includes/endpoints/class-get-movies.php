<?php


namespace Cine\Endpoints;


use Cine\Controllers\REST_API;
use Cine\Core\Post_Types;
use Cine\Core\Taxonomy_Genres;
use Cine\Core\Transients;
use Cine\Models\Movie_Simple_Details;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Throwable;


defined( 'ABSPATH' ) || exit;


final class Get_Movies extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'movies';
	public string $method	= WP_REST_Server::READABLE;


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool 	True, if user can edit posts
	 */
	public function check_permission( WP_REST_Request $request ): bool
	{
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
			'count' => [
				'default' 			=> 50,
				'sanitize_callback' => 'absint',
				'type'				=> 'string',
			],
			'genre'	=> [
				'default'			=> 0,
				'sanitize_callback'	=> 'absint',
				'type'				=> 'string',
			],
			'keyword' => [
				'default'			=> '',
				'sanitize_callback'	=> 'sanitize_text_field',
				'type'				=> 'string',
			],
			'page' => [
				'default' 			=> 1,
				'sanitize_callback' => 'absint',
				'type'				=> 'string',
			],			
			'to_watch' => [
				'default'			=> false,
				'type'				=> 'string',
				'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
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
		$params = $req->get_params();

		// prep response object
		$res = new WP_REST_Response();
		
		try {
			$data = $this->get_from_transients( $params );
			if( false === $data ) {
				$data = $this->query_from_params( $params );
			}

			$res->set_data( $data );
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
	 * Check for cached movie results
	 *
	 * @param 	array 	$params	Request params
	 * @return 	array|false		Array of "meta" and "results" if transient found; otherwise, false
	 */
	private function get_from_transients( array $params ): array|false
	{
		$value = Transients::get_transient( 'get_movies', $params );

		if( false !== $value && isset( $value['meta'] ) && isset( $value['movies'] ) ) {
			return $value;
		}

		return false;
	}


	/**
	 * Get movies (and query meta) from WP query
	 *
	 * @param 	array 	$params 	Request params
	 * @return	array 				Contains "meta" and "movies" props
	 */
	private function query_from_params( array $params ): array
	{
		[
			'count' => $count,
			'genre' => $genre,
			'keyword' => $keyword,
			'page' => $page,
			'to_watch' => $to_watch,
		] = $params;

		$query_args = [
			'fields' => 'ids',
			'meta_query' => [],
			'order' => 'ASC',
			'orderby' => 'title',
			'paged' => $page,
			'post_type' => Post_Types::POST_TYPE_KEY,
			'posts_per_page' => $count,
		];

		// querying for specific category of movies?
		if( !empty( $genre ) ) {
			$query_args['tax_query'] = [
				[
					'field' => 'term_id',
					'taxonomy' => Taxonomy_Genres::TAXONOMY_KEY,
					'terms' => $genre,
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
					'key' => 'to_watch',
					'value' => true,
				]
			];
		}	

		$meta = [];
		$movies = [];
		
		$query = new WP_Query( $query_args );
		
		// convert movie posts in movie blocks with specific movie info
		foreach( $query->posts as $post_id ) {
			$movie = new Movie_Simple_Details( $post_id );
			$movies[] = $movie->package();
		}

		// used to determine if more pages of posts or if last page (e.g. total / page)
		$meta['post_count'] = $query->post_count;
		$meta['total_posts'] = $query->found_posts;

		$data = compact( 'meta', 'movies' );

		// cache data for later requests
		Transients::set_transient( 'get_movies', $query_args, $data );
	
		return $data;
	}
}
