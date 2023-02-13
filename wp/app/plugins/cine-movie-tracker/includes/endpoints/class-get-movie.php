<?php


namespace Cine\Endpoints;

use Cine\Controllers\Helpers;
use Cine\Controllers\REST_API;
use Cine\Core\Post_Types;
use Cine\Core\Taxonomy_Genres;
use Cine\Models\Movie_Full_Details;
use Cine\Models\Movie_Simple_Details;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Throwable;


defined( 'ABSPATH' ) || exit;


final class Get_Movie extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'movie/(?P<id>[\d]+)';
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
			'id' => [
				'required' 			=> true,
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
		
		$movie = new Movie_Full_Details( $id );
		
		$res->set_data(
			[
				'movie' => $movie->package()
			]
		);
		$res->set_status( 200 );

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
