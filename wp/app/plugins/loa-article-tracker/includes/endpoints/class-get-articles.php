<?php


namespace Loa\Endpoints;


use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use WP_Error;


use Loa\Controller\API as API;
use Loa\Model\Article_Block as Article_Block;
use Loa\Abstracts\Endpoint as Endpoint;
use Loa\Traits\Articles_Meta;


defined( 'ABSPATH' ) || exit;


class Get_Articles extends Endpoint
{
	use Articles_Meta;


	public $route	= 'articles';
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
		return [
			'page'	=> [
				'default'			=> 1,
				'type'				=> 'string',
				'sanitize_callback'	=> 'absint',
			],
			'count'	=> [
				'default'			=> 50,
				'type'				=> 'string',
				'sanitize_callback'	=> 'absint',
				'validate_callback' => [ $this, 'check_article_count' ],
			],
			'tag'	=> [
				'default'			=> 0,
				'type'				=> 'string',
				'sanitize_callback'	=> 'absint', // @todo — could be array
			],
			'keyword' => [
				'default'			=> '',
				'type'				=> 'string',
				'sanitize_callback'	=> [ 'Vril_Utility', 'sanitize_var' ],
			],
			'read' => [
				'default'			=> false,
				'type'				=> 'string',
				'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
			],
			'favorite' => [
				'default'			=> false,
				'type'				=> 'string',
				'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],
			],
			'include_meta' => [
				'default'			=> true,
				'type'				=> 'string',
				'sanitize_callback'	=> [ 'Vril_Utility', 'convert_to_bool' ],				
			]
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
		$page 			= $req->get_param( 'page' );
		$count 			= $req->get_param( 'count' );
		$tag 			= $req->get_param( 'tag' );
		$keyword 		= $req->get_param( 'keyword' );
		$is_read 		= $req->get_param( 'read' );
		$is_favorite 	= $req->get_param( 'favorite' );
		$include_meta 	= $req->get_param( 'include_meta' );

		// prep response object
		$res = $this->create_response_obj( 'meta', 'articles' );
		
		try {
			$query_args = [
				'paged'				=> $page,
				'post_type'			=> Loa()->post_types::POST_TYPE,
				'posts_per_page'	=> $count,
				'orderby'			=> 'ID',
				'order'				=> 'ASC', // oldest first
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
						'taxonomy'	=> Loa()->post_types::TAXONOMY,
						'terms'		=> $tag,
					]
				];
			}

			// check for transient data
			$transient = Loa()->transients::get_transient( 'get_articles', $query_args );

			[ 'articles' => $articles ] = $transient;

			if( empty( $articles ) ) {
				$query = new WP_Query( $query_args );
	
				// extract specific data from article posts
				$articles = [];
				foreach( $query->posts as $post ) {
					$article	= new Article_Block( $post );
					$articles[] = $article->package();
	
					unset( $article );
				}

				Loa()->transients::set_transient( 'get_articles', $query_args, compact( 'articles' ) );
			}

			$res->add_data( 'articles', $articles );

			$methods = get_class_methods( $this );

			// additional metadata for frontend
			if( $include_meta ) {
				$last_updated			= Articles_Meta::get_last_updated();
				$total_articles			= Articles_Meta::get_article_count();
				$total_articles_read 	= Articles_Meta::get_article_count_read();
				$total_articles_unread 	= Articles_Meta::get_article_count_unread();
				$total_pages			= ceil( $total_articles / $count );	
				$page_size				= count( $articles );
				$page_index				= ( $total_pages > $page ) ? $page : $total_pages;
			
				$meta = compact( 
					'last_updated',
					'total_articles',
					'total_articles_read',
					'total_articles_unread',
					'total_pages',
					'page_size',
					'page_index',
				);
	
				$res->add_data( 'meta', $meta );
			}

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );				
	}


	/**
	 * Total article count per request cannot exceed 100
	 *
	 * @param	string 	$count 	Count arg for request
	 * @return 	bool|WP_Error 	True, if valid count; otherwise, WP_Error
	 */
	public function check_article_count( string $count = '' )
	{
		$count = absint( $count );

		if( 0 === $count || 100 < $count ) {
			return new WP_Error(
				'loa/endpoints/get-articles/invalid-count',
				sprintf( 'Invalid article count: "%s". Count must be between 1 and 100.', $count )
			);
		}

		return true;
	}


}
