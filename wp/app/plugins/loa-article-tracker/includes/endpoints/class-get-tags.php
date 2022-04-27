<?php


namespace Loa\Endpoints;


use Throwable;
use Exception;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


use Loa\Controller\API as API;
use Loa\Abstracts\Endpoint as Endpoint;


defined( 'ABSPATH' ) || exit;


class Get_Tags extends Endpoint
{
	public $route	= 'tags';
	public $method	= WP_REST_Server::READABLE;


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool 	True; endpoint is public
	 */
	public function check_permission( WP_REST_Request $request ): bool
	{
		return true;
	}


	/**
	 * Establish endpoint arguments
	 *
	 * @return 	array 	Empty array (no args)
	 */
	public function get_route_args(): array
	{
		return [];
	}


	/**
	 * Handle endpoint request
	 *
	 * @param	WP_REST_Request 	$req	API request
	 * @return 	WP_REST_Response			API response
	 */
	public function handle_request( WP_REST_Request $req ): WP_REST_Response
	{
		// prep response object
		$res = $this->create_response_obj( 'meta', 'tags' );

		try {
			// check for transient data
			$transient = Loa()->transients::get_transient( 'get_tags' );

			[ 'tags' => $tags, 'meta' => $meta ] = $transient;

			if( empty( $data ) || empty( $meta ) ) {
				$tax_query = [
					'hide_empty'	=> false,
					'taxonomy'		=> Loa()->post_types::TAXONOMY,
				];
	
				$tags = [];
				foreach( get_terms( $tax_query ) as $term ) {
					$tags[] = [
						'id' 	=> $term->term_taxonomy_id,
						'name' 	=> $term->name,
					];
				}
	
				$tag_count		= count( $tags );
				$last_updated	= Loa()->last_updated->get_timestamp();
				$meta 			= compact( 'last_updated', 'tag_count' );

				Loa()->transients::set_transient( 'get_tags', null, compact( 'tags', 'meta' ) );
			}

			$res->add_data( 'tags', $tags );
			$res->add_data( 'meta', $meta );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}
		
		return rest_ensure_response( $res->package() );			
	}

}
