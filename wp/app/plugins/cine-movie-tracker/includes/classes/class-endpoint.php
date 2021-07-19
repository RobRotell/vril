<?php


namespace Cine;


use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use Exception;


defined( 'ABSPATH' ) || exit;


class Endpoint
{
	const NAMESPACE = 'cine/v2';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}


	public function register_routes()
	{
		$helper = Cine()->helper;

		// grab all movies
		register_rest_route(
			self::NAMESPACE,
			'/get-movies',
			[
				'methods'				=> WP_REST_Server::READABLE,
				'callback'				=> [ $this, 'get_movies' ],
				'permission_callback'	=> '__return_true',
				'args'		=> [
					'page'	=> [
						'default'			=> 1,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'convert_to_int' ],
					],
					'count'	=> [
						'default'			=> 50,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'convert_to_int' ],
					],
					'genre'	=> [
						'default'			=> 0,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'convert_to_int' ],
					],
					'keyword' => [
						'default'			=> '',
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'sanitize_var' ],
					],
					'to_watch' => [
						'default'			=> false,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'convert_to_bool' ],
					],
					'no_cache' => [
						'default'			=> false,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'convert_to_bool' ],
					]
				]
			]
		);

		// grab specific movie
		register_rest_route(
			self::NAMESPACE,
			'/get-movie-by-id',
			[
				'methods'				=> WP_REST_Server::READABLE,
				'callback'				=> [ $this, 'get_movie_by_id' ],
				'permission_callback'	=> '__return_true',
				'args'		=> [
					'id' 	=> [
						'required'			=> true,
						'sanitize_callback'	=> [ $helper, 'convert_to_int' ]
					]
				]
			]
		);		

		// find movie by title
		register_rest_route(
			self::NAMESPACE,
			'/search-by-title',
			[
				'methods'				=> WP_REST_Server::READABLE,
				'callback'				=> [ $this, 'search_by_title' ],
				'permission_callback'	=> '__return_true',
				'args'		=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'validate_callback'	=> [ $this, 'check_auth' ]
					],
					'title'	=> [
						'required' 			=> true,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'sanitize_var' ]
					],
					'limit' => [
						'default'			=> 10,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'convert_to_int' ]
					]
				]
			]
		);

		// add movie by TheMovieDatabase ID
		register_rest_route(
			self::NAMESPACE,
			'/add-movie-by-id',
			[
				'methods'				=> WP_REST_Server::CREATABLE,
				'callback'				=> [ $this, 'add_movie_by_id' ],
				'permission_callback'	=> '__return_true',
				'args'		=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'validate_callback'	=> [ $this, 'check_auth' ]
					],
					'id'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'convert_to_int' ]
					],
					'to_watch' => [
						'default'			=> false,
						'type'				=> 'string',
						'sanitize_callback' => [ $helper, 'convert_to_bool' ]
					]
				]
			]
		);

		// update movie's status
		register_rest_route(
			self::NAMESPACE,
			'/set-movie-as-watched',
			[
				'methods'				=> WP_REST_Server::EDITABLE,
				'callback'				=> [ $this, 'set_movie_as_watched' ],
				'permission_callback'	=> '__return_true',
				'args'		=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'validate_callback'	=> [ $this, 'check_auth' ]
					],
					'id'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'convert_to_int' ]
					],
					'status' => [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback' => [ $helper, 'convert_to_bool' ]
					]
				]
			]
		);	

		// delete movie
		register_rest_route(
			self::NAMESPACE,
			'/delete-movie',
			[
				'methods'				=> WP_REST_Server::DELETABLE,
				'callback'				=> [ $this, 'delete_movie' ],
				'permission_callback'	=> '__return_true',
				'args'		=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'validate_callback'	=> [ $this, 'check_auth' ]
					],
					'id'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback'	=> [ $helper, 'convert_to_int' ]
					]
				]
			]
		);			
	}


	public static function get_endpoint_url()
	{
		return get_rest_url( null, self::NAMESPACE );
	}


	/**
	 * Get all current movies from database
	 *
	 * @return			Description
	 */
	public function get_movies( WP_Rest_Request $request )
	{
		Cine()->helper::load_model( 'Movie Block' );

		$page 		= $request->get_param( 'page' );
		$count 		= $request->get_param( 'count' );
		$genre 		= $request->get_param( 'genre' );
		$keyword 	= $request->get_param( 'keyword' );
		$to_watch 	= $request->get_param( 'to_watch' );
		$no_cache 	= $request->get_param( 'no_cache' );

		$fetch_new 		= false;
		$last_updated 	= Admin::get_last_updated();

		$data = [
			'last_updated' => $last_updated
		];

		if( $no_cache ) {
			$fetch_new = true;
		} else {
			$transient_key = compact( 'page', 'count', 'genre', 'keyword', 'to_watch' );
			$transient_key = http_build_query( $transient_key );
			$transient_key = sprintf( 'cine_fetch_%s', md5( $transient_key ) );
	
			$cached_data = get_transient( $transient_key );
			if( !isset( $cached_data['last_updated'] ) || $last_updated !== $cached_data['last_updated'] ) {
				$fetch_new = true;
			} elseif( !isset( $cached_data['movies'] ) || empty( $cached_data['movies'] ) ) {
				$fetch_new = true;
			} else {
				$data = $cached_data;
			}
		}

		if( $fetch_new ) {
			$movies = [];

			$query_args = [
				'post_type' 		=> Core::POST_TYPE,
				'posts_per_page'	=> $count,
				'paged'				=> $page,
				'order'				=> 'ASC',
				'orderby'			=> 'meta_value',
				'meta_key'			=> 'title_for_compare',
			];

			if( !empty( $keyword ) ) {
				$query_args['s'] = $keyword;
			}

			if( !empty( $genre ) ) {
				$query_args['tax_query'] = [
					[
						'taxonomy'	=> Core::TAXONOMY,
						'terms'		=> $genre
					]
				];
			}

			if( $to_watch ) {
				$query_args['meta_query'] = [
					[
						'key'	=> 'to_watch',
						'value'	=> true
					]
				];
			}

			$posts = get_posts( $query_args );

			foreach( $posts as $post ) {
				$movies[] = new Movie_Block( $post );
			}

			// get simplified form of movie
			foreach( $movies as &$movie ) {
				$movie = $movie->prep_movie();
			}
			unset( $movie );
			
			$data['movies'] = $movies;

			if( !$no_cache ) {
				set_transient( $transient_key, $data );
			}
		}

		$response = new WP_REST_Response( $data );

		return rest_ensure_response( $response );
	}


	/**
	 * Get movie by post ID
	 *
	 * @param	WP_Request 	$request
	 * @return	Movie_Details
	 */
	public function get_movie_by_id( WP_Rest_Request $request )
	{
		$id = $request->get_param( 'id' );

		// get post
		$post = get_post( $id );
		if( !is_a( $post, 'WP_Post' ) )
			return false;

		$movie = new Display_Movie( $post );
		$movie->grab_all_details();

		return $movie;
	}


	public function search_by_title( WP_Rest_Request $request )
	{
		$title = $request->get_param( 'title' );
		$limit = $request->get_param( 'limit' );

		// what we'll return
		$matches = [];

		// query TheMovieDatabase for movies that match title
		$results = Fetcher::find_movie_by_title( $title, $limit );
		
		// convert to pretty search results
		$i = 1;
		foreach( $results as $result ) {
			if( $i > $limit )
				break;

			$result = new Search_Result( $result );
			$result = $result->get_result();
			if( !empty( $result ) ) {
				$matches[] = $result;
				$i++;
			}
		}

		return $matches;
	}


	public function add_movie_by_id( WP_Rest_Request $request )
	{
		$tmdb_id = $request->get_param( 'id' );
		$status = $request->get_param( 'to_watch' );

		// query TMDB for details for movie
		$details = Fetcher::find_movie_details( $tmdb_id );

		// setup basic movie
		$movie = new Raw_Movie( $details );

		// query TMDB for movie credits
		$credits = Fetcher::find_movie_credits( $tmdb_id );
		$movie->set_credits( $credits );
		
		// set watch status
		$movie->set_watch_status( $status );

		// save as movie post
		$post_id = $movie->save_as_post();

		return new Display_Movie( get_post( $post_id ) );
	}


	public function set_movie_as_watched( WP_Rest_Request $request )
	{
		$movie_id = $request->get_param( 'id' );
		$status = $request->get_param( 'status' );

		// update movie's status
		update_field( 'to_watch', !$status, $movie_id );

		return new Display_Movie( get_post( $movie_id ) );
	}	


	public function delete_movie( WP_Rest_Request $request )
	{
		$movie_id = $request->get_param( 'id' );

		// first, delete image for movie poster
		if( !empty( $thumb_id = get_post_thumbnail_id( $movie_id ) ) )
			wp_delete_attachment( $thumb_id, true );

		// second, delete image for backdrop
		if( !empty( $attachment_id = get_field( 'backdrop', $movie_id ) ) )
			wp_delete_attachment( $attachment_id, true );

		// now, delete the movie
		return wp_delete_post( $movie_id, true );
	}		


	/**
	 * Check if user is authorized for action
	 *
	 * @param	string	$auth 	Submitted authorization code
	 * @return	bool 			True, if authorized
	 */
	public function check_auth( string $value = '' ): bool
	{
		return ( $value === get_option( Admin::get_auth() ) );
	}

}
