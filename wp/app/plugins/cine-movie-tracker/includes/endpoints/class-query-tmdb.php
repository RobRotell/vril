<?php


namespace Cine\Endpoints;


use Cine\Controllers\REST_API;
use Cine\Controllers\TMDb;
use Cine\Core\Transients;
use Cine\Model\TMDb_Movie_Result;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


defined( 'ABSPATH' ) || exit;


final class Query_TMDb extends \Vril\Core_Classes\REST_API_Endpoint
{
	protected $namespace	= REST_API::NAMESPACE;
	public string $route	= 'query-tmdb';
	public string $method	= WP_REST_Server::CREATABLE;


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
			'title'	=> [
				'required' 			=> true,
				'sanitize_callback'	=> 'sanitize_text_field',
				'type'				=> 'string',
			],
			'page' => [
				'default'			=> 1,
				'sanitize_callback'	=> 'absint',
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

		// don't exceed 

		// prep response object
		$res = $this->create_response_obj( 'meta', 'params', 'results' );
		$res->add_data( 'params', $params );
		
		try {
			$data = $this->get_from_transients( $params );
			if( empty( $data ) ) {
				$data = $this->get_from_query( $params );
			}

			$res
				->add_data( 'meta', $data['meta'] )
				->add_data( 'results', $data['results'] );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );			
	}


	/**
	 * Get results from TMDB (from previous queries) from transients
	 *
	 * @param 	array 	$params 	Request params
	 * @return 	array|false 		Array of "meta" and "results" if transient found; otherwise, false
	 */
	private function get_from_transients( array $params ): array|false
	{
		return false;

		$key	= sprintf( 'query_tmdb_%s', http_build_query( $params ) );
		$value 	= Transients::get_transient( $key );

		if( isset( $value['meta'] ) && isset( $value['results'] ) ) {
			return $value;
		}

		return false;
	}


	/**
	 * Get results (and query meta) from TMDB
	 *
	 * @param 	array 	$params 	Request params
	 * @return	array 				Contains "meta" and "results" props
	 */
	private function get_from_query( array $params ): array
	{
		$meta 		= [];
		$results	= [];

		[
			'page'	=> $page,
			'title' => $title,
		] = $params;

		$search_results = TMDb::find_movie_by_title( $title, $page );
		
		foreach( $search_results['page_results'] as $raw_tmdb_movie ) {
			$result = new TMDb_Movie_Result( $raw_tmdb_movie );
			$results[] = get_object_vars( $result );
		}

		$meta['result_count']	= count( $results );
		$meta['total_pages'] 	= $search_results['total_pages'];
		$meta['total_results'] 	= $search_results['total_results'];

		$data = compact( 'meta', 'results' );

		// save transient for later queries
		$transient_key = sprintf( 'query_tmdb_%s', http_build_query( $params ) );
		Transients::set_transient( $transient_key, null, $data );

		return $data;
	}
}
