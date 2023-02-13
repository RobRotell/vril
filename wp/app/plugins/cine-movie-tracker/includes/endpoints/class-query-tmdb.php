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
use Exception;
use Throwable;


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

		if( 0 === $current_user->ID ) {
			return new WP_Error(
				'cine/endpoint/query_tmdb/invalid_user',
				'Invalid authorization. Check your authorization and try again.',
				[ 
					'status' => 401
				]
			);
		}

		if( !$current_user->has_cap( 'edit_posts' ) ) {
			return new WP_Error(
				'cine/endpoint/query_tmdb/invalid_permissions',
				'You are not permitted to perform this type of query.',
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

		// prep response object
		$res = new WP_REST_Response();
		
		try {
			$data = $this->get_from_transients( $params );
			if( false === $data ) {
				$data = $this->query_tmdb( $params );
			}

			$res->set_data( $data);
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
	 * Get results from TMDB (from previous queries) from transients
	 *
	 * @param 	array 	$params	Request params
	 * @return 	array|false		Array of "meta" and "results" if transient found; otherwise, false
	 */
	private function get_from_transients( array $params ): array|false
	{
		$value = Transients::get_transient( 'query_tmdb', $params );

		if( false !== $value && isset( $value['meta'] ) && isset( $value['results'] ) ) {
			return $value;
		}

		return false;
	}


	/**
	 * Get results(and query meta from TMDB
	 *
	 * @param 	array 	$params	Request params
	 * @return	array			Contains "meta" and "results" props
	 */
	private function query_tmdb( array $params ): array
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
			$results[] = $result->package();
		}

		$meta['result_count']	= count( $results );
		$meta['total_pages'] 	= $search_results['total_pages'];
		$meta['total_results'] 	= $search_results['total_results'];

		$data = compact( 'meta', 'results' );

		// cache data for later requests
		Transients::set_transient( 'query_tmdb', $params, $data );

		return $data;
	}
}
