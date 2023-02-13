<?php


namespace Loa\Endpoints;


use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use WP_Error;


use Loa\Controller\API as API;
use Loa\Abstracts\Endpoint as Endpoint;
use Loa\Traits\Articles_Meta;


defined( 'ABSPATH' ) || exit;


class Get_Meta extends Endpoint
{
	use Articles_Meta;


	public $route	= 'meta';
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
	 * @param	WP_Rest_Request 	$req	API request
	 * @return 	WP_REST_Response			API response
	 */
	public function handle_request( WP_Rest_Request $req ): WP_REST_Response
	{
		// prep response object
		$res = $this->create_response_obj( 'meta' );
		
		try {
			$last_updated			= Articles_Meta::get_last_updated();
			$total_articles			= Articles_Meta::get_article_count();
			$total_articles_read 	= Articles_Meta::get_article_count_read();
			$total_articles_unread 	= Articles_Meta::get_article_count_unread();
		
			$meta = compact( 
				'last_updated',
				'total_articles',
				'total_articles_read',
				'total_articles_unread',
			);

			$res->add_data( 'meta', $meta );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );				
	}

}
