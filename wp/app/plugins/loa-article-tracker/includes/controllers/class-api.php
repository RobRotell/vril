<?php


namespace Loa\Controller;


use Exception;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_Query;


use Loa\Model\Api_Response as Api_Response;
use Loa\Model\Article_Block as Article_Block;
use Loa\Model\New_Article as New_Article;


defined( 'ABSPATH' ) || exit;


class API
{
	/**
	 * Create standardized response object
	 *
	 * @param	string			$keys 	Data keys
	 * @return	Api_Response 			Custom API response obj
	 */
	private static function create_response_obj( string ...$keys ): Api_Response
	{
		$response = new Api_Response();

		foreach( $keys as $key ) {
			$response->add_data_key( $key );
		}

		return $response;
	}


	/**
	 * Handle request for getting articles
	 *
	 * @param	WP_Rest_Request		$request	API request
	 * @return 	WP_REST_Response 				API response
	 */
	public function get_articles( WP_Rest_Request $request ): WP_REST_Response
	{
		$page 			= $request->get_param( 'page' );
		$count 			= $request->get_param( 'count' );
		$tag 			= $request->get_param( 'tag' );
		$keyword 		= $request->get_param( 'keyword' );
		$is_read 		= $request->get_param( 'read' );
		$is_favorite 	= $request->get_param( 'favorite' );

		// prep response object
		$res = self::create_response_obj( 'meta', 'articles' );

		try {
			$query_args = [
				'paged'				=> $page,
				'post_type'			=> Loa()->core::POST_TYPE,
				'posts_per_page'	=> $count,
				'meta_query'		=> [
					'relation' => 'AND',
					[
						'key' => 'article_favorite',
						'value' => '1',
						'compare' => ( $is_favorite ) ? '=' : '!='
					],
					[
						'key' => 'article_read',
						'value' => '1',
						'compare' => ( $is_read ) ? '=' : '!='
					],					
				]
			];

			if( !empty( $keyword ) ) {
				$query_args['s'] = $keyword;
			}

			if( !empty( $tag ) ) {
				$query_args['tax_query'] = [
					[
						'taxonomy'	=> Loa()->core::TAXONOMY,
						'terms'		=> $tag,
					]
				];
			}

			$query = new WP_Query( $query_args );

			// extract specific data from article posts
			$articles = [];
			foreach( $query->posts as $post ) {
				$article	= new Article_Block( $post );
				$articles[] = $article->package();

				unset( $article );
			}
			$res->add_data( 'articles', $articles );

			// additional metadata for frontend
			$last_updated			= Loa()->admin::get_last_updated();
			$page_size				= count( $articles );
			$total_articles			= Loa()->helper::get_unread_articles( true );
			$total_pages			= ceil( $total_articles / $count );	
			$page_index				= ( $total_pages > $page ) ? $page : $total_pages;
			$total_read_articles	= Loa()->helper::get_read_articles( true );
			
			$meta = compact( 
				'last_updated', 
				'page_index', 
				'page_size', 
				'total_pages',
				'total_articles', 
				'total_read_articles'
			);

			$res->add_data( 'meta', $meta );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );		
	}	


	/**
	 * Handle request for getting all article categories
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */
	public function get_tags( WP_Rest_Request $request ): WP_REST_Response
	{
		// prep response object
		$res = self::create_response_obj( 'meta', 'tags' );

		try {
			$tax_query = [
				'hide_empty' 	=> false,
				'taxonomy' 		=> Loa()->core::TAXONOMY,
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
			$last_updated 	= Loa()->admin::get_last_updated();

			$meta = compact( 'last_updated', 'total_count' );

			$res->add_data( 'meta', $meta );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );				
	}


	/**
	 * Handle request for updating an article's read/favorite status
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */
	public function update_article( WP_Rest_Request $request ): WP_REST_Response
	{
		$article_id	= $request->get_param( 'id' );
		$read		= $request->get_param( 'read' );
		$favorite	= $request->get_param( 'favorite' );

		// prep response object
		$res = self::create_response_obj( 'meta', 'article' );

		try {
			$post = get_post( $article_id );
			if( empty( $post ) || $post->post_type !== Loa()->core::POST_TYPE ) {
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


	/**
	 * Handle request for adding a new article
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */
	public function add_article( WP_Rest_Request $request ): WP_REST_Response
	{
		$url 		= $request->get_param( 'url' );
		$tags		= $request->get_param( 'tags' );
		$read		= $request->get_param( 'read' );
		$favorite 	= $request->get_param( 'favorite' );

		// prep response object
		$res = self::create_response_obj( 'meta', 'article' );

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


	/**
	 * Check that auth is correct doe
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */
	public function check_auth( WP_Rest_Request $request ): WP_REST_Response
	{
		// prep response object
		$auth = $request->get_param( 'auth' );

		$res = self::create_response_obj();

		$is_valid = Loa()->admin::check_auth( $auth );

		$res->add_data( 'valid', $is_valid );

		return rest_ensure_response( $res->package() );				
	}			


	/**
	 * Get last updated time
	 * 
	 * Apps can use this option as a way to quickly check if the values saved in cache/local storage are out-of-date. 
	 * A greater last updated time denotes that content has changed since last request.
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */
	public function get_last_updated_time( WP_Rest_Request $request ): WP_REST_Response
	{
		// prep response object
		$res = self::create_response_obj();

		$res->add_data( 'last_updated', Admin::get_last_updated() );

		return rest_ensure_response( $res->package() );
	}	

}
