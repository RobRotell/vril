<?php


namespace Cine;


use Exception;
use Throwable;
use Vril_Utility;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;


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
		$api = Cine()->api;

		$permission_callback_public	= '__return_true';
		$permission_callback_auth	= [ $this, 'check_auth' ];

		// grab subset of movies
		register_rest_route(
			self::NAMESPACE,
			'/get-movies',
			[
				'methods'				=> WP_REST_Server::READABLE,
				'callback'				=> [ $api, 'get_movies' ],
				'permission_callback'	=> $permission_callback_public,
				'args'		=> [
					'page'	=> [
						'default'			=> 1,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ],
					],
					'count'	=> [
						'default'			=> 50,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ],
					],
					'genre'	=> [
						'default'			=> 0,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ],
					],
					'keyword' => [
						'default'			=> '',
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'sanitize_var' ],
					],
					'to_watch' => [
						'default'			=> false,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					],
					'no_cache' => [
						'default'			=> false,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					]
				]
			]
		);

		// grab subset of movies
		register_rest_route(
			self::NAMESPACE,
			'/get-last-updated-time',
			[
				'methods'				=> WP_REST_Server::READABLE,
				'callback'				=> [ $api, 'get_last_updated_time' ],
				'permission_callback'	=> $permission_callback_public,
			]
		);		

		// grab specific movie
		register_rest_route(
			self::NAMESPACE,
			'/get-movie-by-id',
			[
				'methods'				=> WP_REST_Server::READABLE,
				'callback'				=> [ $api, 'get_movie_by_id' ],
				'permission_callback'	=> $permission_callback_public,
				'args'		=> [
					'id' 	=> [
						'required'			=> true,
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ]
					]
				]
			]
		);		

		// find movie by title
		register_rest_route(
			self::NAMESPACE,
			'/search-by-title',
			[
				'methods'				=> WP_REST_Server::CREATABLE,
				'callback'				=> [ $api, 'search_by_title' ],
				'permission_callback'	=> $permission_callback_auth,
				'args'		=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'validate_callback'	=> [ 'Vril_Utility', 'sanitize_var' ]
					],
					'title'	=> [
						'required' 			=> true,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'sanitize_var' ]
					],
					'limit' => [
						'default'			=> 10,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ]
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
				'callback'				=> [ $api, 'add_movie_by_id' ],
				'permission_callback'	=> $permission_callback_auth,
				'args'		=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'validate_callback'	=> [ 'Vril_Utility', 'sanitize_var' ]
					],
					'id'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ]
					],
					'to_watch' => [
						'default'			=> false,
						'type'				=> 'string',
						'sanitize_callback' => [ 'Vril_Utility', 'convert_to_bool' ]
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
				'callback'				=> [ $api, 'set_movie_as_watched' ],
				'permission_callback'	=> $permission_callback_auth,
				'args'		=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'validate_callback'	=> [ 'Vril_Utility', 'sanitize_var' ]
					],
					'id'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ]
					],
					// 'watched' => [
					// 	'required'			=> true,
					// 	'type'				=> 'string',
					// 	'sanitize_callback' => [ 'Vril_Utility', 'convert_to_bool' ]
					// ]
				]
			]
		);	

		// delete movie
		register_rest_route(
			self::NAMESPACE,
			'/delete-movie',
			[
				'methods'				=> WP_REST_Server::DELETABLE,
				'callback'				=> [ $api, 'delete_movie' ],
				'permission_callback'	=> $permission_callback_auth,
				'args'		=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'validate_callback'	=> [ 'Vril_Utility', 'sanitize_var' ]
					],
					'id'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_int' ]
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
	 * Check if user is authorized for action
	 *
	 * @param	WP_REST_Request	$auth 	Submitted authorization code
	 * @return	bool 					True, if authorized
	 */
	public static function check_auth( WP_REST_Request $request ): bool
	{
		$auth = $request->get_param( 'auth' );

		return Cine()->admin::check_auth( $auth );
	}

}
