<?php


namespace Loa\Endpoints;


use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use WP_Error;


use Loa\Controller\API as API;
use Loa\Model\New_Article as New_Article;
use Loa\Model\Article_Block as Article_Block;
use Loa\Abstracts\Endpoint as Endpoint;
use Loa\Traits\Articles_Meta;


defined( 'ABSPATH' ) || exit;


class Add_Article extends Endpoint
{
	use Articles_Meta;


	public $route 	= 'articles';
	public $method 	= WP_REST_Server::CREATABLE;


	/**
	 * Handle permission check for endpoint
	 *
	 * @return 	bool 	True, if user can edit posts
	 */
	public function check_permission( WP_REST_Request $request ): bool
	{
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
			'url'	=> [
				'required'			=> true,
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback'	=> [ $this, 'validate_url' ],
			],
			'tags'	=> [
				'default'			=> [],
				'type'				=> 'array',
				'sanitize_callback'	=> [ Loa()->helper, 'clean_tags' ],
			],
			'read'	=> [
				'default'			=> false,
				'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
			],
			'favorite' => [
				'default'			=> false,
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
		$url 			= $req->get_param( 'url' );
		$tags			= $req->get_param( 'tags' );
		$read			= $req->get_param( 'read' );
		$favorite 		= $req->get_param( 'favorite' );
		$include_meta 	= $req->get_param( 'include_meta' );

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


	/**
	 * Confirm that input is actually a URL
	 *
	 * @param	string 	$url	URL included in request
	 * @return 	bool|WP_Error 	True, if valid URL; otherwise, WP_Error 
	 */
	public function validate_url( string $url = '' )
	{
		$url = esc_url_raw( $url );

		if( empty( $url ) ) {
			return new WP_Error(
				'loa/endpoints/add-article/invalid-url',
				'Invalid URL for article'
			);
		}

		return true;
	}

}
