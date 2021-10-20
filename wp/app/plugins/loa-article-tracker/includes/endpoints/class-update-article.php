<?php


namespace Loa\Endpoints;


use Exception;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;


use Loa\Controller\API as API;
use Loa\Model\New_Article as New_Article;
use Loa\Model\Article_Block as Article_Block;


defined( 'ABSPATH' ) || exit;


class Update_Article extends \Loa\Abstracts\Endpoint
{
	public $route = 'update-article';


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
					'id'	=> [
						'required'			=> true,
						'type'				=> 'string',
						'sanitize_callback' => 'absint',
					],					
					'read'	=> [
						'default'			=> null,
						'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
					],
					'favorite' => [
						'default'			=> null,
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
		$article_id	= $req->get_param( 'id' );
		$read		= $req->get_param( 'read' );
		$favorite	= $req->get_param( 'favorite' );

		// prep response object
		$res = $this->create_response_obj( 'meta', 'article' );

		try {
			$post = get_post( $article_id );
			if( empty( $post ) || $post->post_type !== Loa()->post_types::POST_TYPE ) {
				throw new Exception(
					sprintf(
						'No article matches ID: "%s"',
						$article_id
					)
				);
			}

			// update read status
			if( is_bool( $read ) ) {
				update_field( 'article_read', $read, $article_id );
	
				// confirm that read status was correctly updated
				if( $read !== get_field( 'article_read', $article_id ) ) {
					throw new Exception(
						sprintf(
							'Failed to update read status for article (ID: "%s")',
							$article_id
						)
					);
				}
			}

			// update favorite status
			if( is_bool( $favorite ) ) {
				update_field( 'article_favorite', $favorite, $article_id );
	
				// confirm that favorite status was correctly updated
				if( $favorite !== get_field( 'article_favorite', $article_id ) ) {
					throw new Exception(
						sprintf(
							'Failed to update favorite status for article (ID: "%s")',
							$article_id
						)
					);
				}
			}

			// return updated article to frontend
			$article = new Article_Block( $post );
			$article->package();

			$res->add_data( 'article', $article );

			// update metadata for frontend
			// update metadata for frontend
			$total_articles 		= Loa()->helper::get_unread_articles( true );
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
