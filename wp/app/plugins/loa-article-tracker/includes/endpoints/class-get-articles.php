<?php


namespace Loa\Endpoints;


use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;


use Loa\Controller\API as API;
use Loa\Model\Article_Block as Article_Block;
use Loa\Abstracts\Endpoint as Endpoint;


defined( 'ABSPATH' ) || exit;


class Get_Articles extends Endpoint
{
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
			// $last_updated			= Loa()->last_updated->get_timestamp();
			// $page_size				= count( $articles );
			// $total_articles			= Loa()->helper::get_unread_articles( true );
			// $total_pages			= ceil( $total_articles / $count );	
			// $page_index				= ( $total_pages > $page ) ? $page : $total_pages;
			// $total_read_articles	= Loa()->helper::get_read_articles( true );
			
			// $meta = compact( 
			// 	'last_updated', 
			// 	'page_index', 
			// 	'page_size', 
			// 	'total_pages',
			// 	'total_articles', 
			// 	'total_read_articles'
			// );

			// $res->add_data( 'meta', $meta );

		} catch( Throwable $e ) {
			var_dump( $e );
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );				
	}


}
