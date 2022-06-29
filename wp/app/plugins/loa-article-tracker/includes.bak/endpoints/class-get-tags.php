<?php


namespace Loa\Endpoints;


use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;


use Loa\Controller\API as API;
use Loa\Model\New_Article as New_Article;
use Loa\Model\Article_Block as Article_Block;
use Loa\Abstracts\Endpoint as Endpoint;


defined( 'ABSPATH' ) || exit;


class Get_Tags extends Endpoint
{
	public $route = 'get-tags';


	/**
	 * Registers API route
	 *
	 * @return 	void
	 */
	public function register_route()
	{
		register_rest_route(
			API::NAMESPACE,
			$this->get_route(),
			[
				'callback'				=> [ $this, 'handle_request' ],
				'methods'				=> WP_REST_Server::READABLE,
				'permission_callback' 	=> '__return_true',
			]
		);
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
			$res->add_data( 'tags', $tags );

			$total_count = count( $tags );
			$last_updated = Loa()->last_updated->get_timestamp();

			$meta = compact( 'last_updated', 'total_count' );

			$res->add_data( 'meta', $meta );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );			
	}

}
