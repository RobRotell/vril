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
use Loa\Abstracts\Endpoint as Endpoint;
use Loa\Traits\Articles_Meta;


defined( 'ABSPATH' ) || exit;


class Update_Article extends Endpoint
{
	use Articles_Meta;

	public $route 	= 'articles/(?P<id>[\d]+)';
	public $method 	= WP_REST_Server::EDITABLE;


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
			],
			'include_meta' => [
				'default'			=> true,
				'type'				=> 'string',
				'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],				
			],				
		];
	}


	/**
	 * Handle endpoint request
	 *
	 * @param	WP_Rest_Request 	$req	API request
	 * @return 	WP_REST_Response			API response
	 */
	public function handle_request( WP_Rest_Request $req ): WP_REST_Response
	{
		$article_id		= $req->get_param( 'id' );
		$read			= $req->get_param( 'read' );
		$favorite		= $req->get_param( 'favorite' );
		$include_meta 	= $req->get_param( 'include_meta' );

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
				if( !$read === get_field( 'article_read', $article_id ) ) {
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
				if( !$favorite === get_field( 'article_favorite', $article_id ) ) {
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

			if( $include_meta ) {
				$meta = [
					'last_updated' 			=> Articles_Meta::get_last_updated(),
					'total_articles' 		=> Articles_Meta::get_article_count(),
					'total_articles_read' 	=> Articles_Meta::get_article_count_read(),
					'total_articles_unread'	=> Articles_Meta::get_article_count_unread(),
				];
	
				$res->add_data( 'meta', $meta );
			}			

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );			
	}

}
