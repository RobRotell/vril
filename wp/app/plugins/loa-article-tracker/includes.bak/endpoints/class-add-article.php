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


defined( 'ABSPATH' ) || exit;


class Add_Article extends \Loa\Abstracts\Endpoint
{
	public $route = 'articles';


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
				'methods'				=> WP_REST_Server::EDITABLE,
				'permission_callback'	=> [ 'API', 'check_auth' ],
				'args' 					=> [
					'auth'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback' => [ 'Vril_Utility', 'sanitize_var' ],
					],
					'url'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback' => 'esc_url_raw',
					],
					'tags'	=> [
						'default'			=> null,
						'type'				=> 'string',
						'sanitize_callback'	=> [ Loa()->helper, 'clean_tags' ],
					],
					'read'	=> [
						'default'			=> false,
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					],
					'favorite' => [
						'default'			=> false,
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					]
				]
			]
		);
	}


	/**
	 * Handle endpoint request
	 *
	 * @param	WP_Rest_Request 	$req	API request
	 * @return 	WP_REST_Response			API response
	 */
	public function handle_request( WP_Rest_Request $req ): WP_REST_Response
	{
		$url 		= $req->get_param( 'url' );
		$tags		= $req->get_param( 'tags' );
		$read		= $req->get_param( 'read' );
		$favorite 	= $req->get_param( 'favorite' );

		// prep response object
		$res = $this->create_response_obj( 'meta', 'article' );

		try {
			$article = new New_Article( $url );

			// setup basic article
			$article
				->set_tags( $tags )
				->set_read_status( $read )
				->set_favorite_status( $favorite );

			// save new article
			$post_id = $article->save_as_post();

			// get article details for frontend
			$article = new Article_Block( get_post( $post_id ) );

			$res->add_data( 'article', $article->package() );

			// update metadata for frontend
			$total_articles 		= Loa()->helper::get_total_article_count( true );
			$total_read_articles 	= Loa()->helper::get_read_articles( true );
			
			$meta = compact( 
				'total_articles', 
				'total_read_articles', 
			);

			$res->add_data( 'meta', $meta );
			
		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );			
	}

}
