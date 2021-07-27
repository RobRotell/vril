<?php

namespace Loa_Article_Tracker;

use WP_REST_Request;
use Exception;


defined( 'ABSPATH' ) || exit;


class Rest_Endpoint
{
	public $auth;
	public $transient = 'loa_cached_everything2';
		
	protected static $_instance = null;
	public static function _instance()
	{
		if( !isset( self::$_instance ) ) {
			$class_name = __CLASS__;
			self::$_instance = new $class_name;
		}

		return self::$_instance;
	}


	public function __construct()
	{
		require_once( LoaArticleTracker()->plugin_inc_path . '/models/class-article.php' );

		$this->auth = md5( 'test' );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}


	public function register_routes()
	{
		$namespace = 'article-repo';

		register_rest_route(
			$namespace,
			'/get-everything',
			[
				'methods'	=> 'GET',
				'callback'	=> [ $this, 'get_everything' ],
			]
		);

		register_rest_route(
			$namespace,
			'/get-tags',
			[
				'methods'	=> 'GET',
				'callback'	=> [ $this, 'get_tags' ],
			]
		);

		register_rest_route(
			$namespace,
			'/get-articles',
			[
				'methods'	=> 'GET',
				'callback'	=> [ $this, 'get_articles' ],
			]
		);	
		
		register_rest_route(
			$namespace,
			'/add-article',
			[
				'methods'	=> 'POST',
				'callback'	=> [ $this, 'add_article' ],
				'args'		=> [
					'auth'	=> [
						'required'	=> true,
						'type'		=> 'string'
					],
					'url'	=> [
						'required'	=> true,
						'type'		=> 'string'
					],
					'tags'	=> [
						'required'	=> false,
						'type'		=> 'string'
					]
				]
			]
		);

		register_rest_route(
			$namespace,
			'/update-article',
			[
				'methods'	=> 'POST',
				'callback'	=> [ $this, 'update_article' ],
				'args'		=> [
					'auth'	=> [
						'required'	=> true,
						'type'		=> 'string'
					],
					'id'		=> [
						'required'	=> true,
						'type'		=> [ 'string', 'integer' ]
					],
					'action' 	=> [
						'required'	=> false,
						'type'		=> 'string'
					]
				]
			]
		);		
		
	}


	/**
	 * Grab all tags and all articles
	 * @return	array 	[ tags, articles ]
	 */
	public function get_everything()
	{
		// $result = get_transient( $this->transient );
		// if( empty( $result ) ) {
			$result = [
				'tags' 		=> $this->get_tags(),
				'articles'	=> $this->get_articles(),
				'totalRead'	=> $this->get_total_read()
			];

			// set_transient( $this->transient, $result, 1800 );
		// }

		wp_send_json( $result );
	}


	/**
	 * Grab all available article categories
	 * @return	array 	Contains all article categories
	 */
	public function get_tags()
	{
		$terms = get_terms(
			[
				'taxonomy'		=> 'article-cat',
				'hide_empty'	=> false
			]
		);

		$tags = [];
		foreach( $terms as $term ) {
			$tags[] = [
				'id'	=> $term->term_id,
				'name'	=> $term->name
			];
		}

		return $tags;
	}


	/**
	 * Grab all articles
	 * 
	 * @return	array 	Contains all articles
	 */
	public function get_articles()
	{
		$posts = get_posts(
			[
				'post_type'         => 'article',
				'posts_per_page'    => -1,
				'meta_query'		=> [
					[
						'key'		=> 'article_date_read',
						'value'		=> '',
						'compare'	=> 'NOT EXISTS'
					]
				]
			]
		);

		$articles = [];
		foreach( $posts as $post ) {
			// $article = new Article( $post->ID );
			$article = new Article( $post );

			// if( $article->is_unread() )
				$articles[] = $article->get_atts();
		}

		return $articles;
	}	



	/**
	 * Get a count of total read articles
	 * 
	 * @return	int 	Total read articles
	 */
	public function get_total_read()
	{
		$posts = get_posts(
			[
				'post_type'         => 'article',
				'posts_per_page'    => -1,
				'meta_query'		=> [
					[
						'key'		=> 'article_date_read',
						'compare'	=> 'EXISTS'
					]
				]
			]
		);	
		
		return count( $posts );
	}		



	/**
	 * Add new article
	 * 
	 * @param 	WP_Rest_Request	$request 	Form submission
	 * @return	mixed 						True, on successful add; string if error
	 */
	public function add_article( WP_REST_Request $request )
	{
		// check if authorized
		$auth = $request->get_param( 'auth' );
		$this->check_auth( $auth );

		$url 	= $request->get_param( 'url' );
		$tags 	= $request->get_param( 'tags' );

		try {
			// valid URL?
			$url = filter_var( $url, FILTER_SANITIZE_URL );
			if( empty( $url ) )
				throw new Exception( 'Link is not a valid URL' );

			// use only HTTPS
			if( strpos( $url, 'http://' ) !== false )
				$url = str_replace( 'http://', 'https://', $url );

			// link already saved?
			$articles = get_posts(
				[
					'post_type'     => 'article',
					'meta_key'      => 'article_url',
					'meta_value'    => $url
				]
			);
			
			if( !empty( $preexisting ) )
				throw new Exception( 'Link already saved' );

			$title = $url; 

			// try to retrieve remote page to get page's title
			$response = wp_safe_remote_get( $url );
			if( 200 === wp_remote_retrieve_response_code( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				if( 
					!empty( $start = strpos( $body, '<title>' ) ) &&
					!empty( $end = strpos( $body, '</title>' ) )
				) {
					$start = $start + 7; // strlen( '<title>' )
					$title = substr( $body, $start, ( $end - $start ) );
					$title = wp_strip_all_tags( $title );
					$title = filter_var( $title, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );

					if( strlen( $title ) > 20 )
						$title = sprintf( '%s [...]', substr( $title, 0, 20 ) );
				}
			}	

			// save URL as new article
			$result = wp_insert_post(
				[
					'post_title'	=> $title,
					'post_type'		=> 'article',
					'post_status'	=> 'publish',
					'meta_input'	=> [
						'article_date_added'	=> date( 'Y-m-d', time() ),
						'article_url'			=> $url
					]
				]
			);

			if( $result === 0 ) {
				throw new Exception( 'Error creating article' );
			} elseif( is_a( $result, 'WP_Error' ) ) {
				throw new Exception( $result->get_error_message() );
			} else {
				$article_id = $result;
			}

			// check for tags to add 
			if( !empty( $tags ) ) {
				if( !is_array( $tags ) )
					$tags = explode( ',', $tags );

				$tags = array_map( 'intval', $tags );
				$tags = array_filter( array_unique( $tags ) );

				wp_set_object_terms( $article_id, $tags, 'article-cat' );
			}

			// clear transient
			delete_transient( $this->transient );

			// return article (to append to articles)
			$article = new Article( get_post( $article_id ) );
			return wp_send_json_success( $article->get() );

		// encounter an error?
		} catch( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
			die;
		}
	}


	/**
	 * Update article status
	 * 
	 * @param 	WP_Rest_Request	$request 	Form submission
	 * @return	mixed 						True, on successful update; string if error
	 */
	public function update_article( WP_REST_Request $request )
	{
		// check if authorized
		$auth = $request->get_param( 'auth' );
		$this->check_auth( $auth );

		$id		= intval( $request->get_param( 'id' ) );
		$action = $request->get_param( 'action' );

		try {
			if( empty( $id ) )
				throw new Exception( 'No article ID provided' );

			if( empty( $action ) )
				throw new Exception( 'No action provided' );

			// check that article actually exists
			$post = get_post( $id );
			if( empty( $post ) || $post->post_type !== 'article' )
				throw new Exception( 'Invalid article ID' );

			// update article
			if( $action === 'read' ) {
				$status = update_field( 'article_date_read', date( 'Y-m-d', time() ), $id );
			} elseif( $action === 'favorited' ) {
				$status = update_field( 'article_is_favorite', true, $id );
			} else {
				throw new Exception( 'Invalid article action' );
			}

			if( !$status ) {
				throw new Exception( 'Failed to update article' );
			} else {

				// clear transient
				delete_transient( $this->transient );

				wp_send_json_success( true );
			}

		} catch( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
			die;
		}
	}
	



	/**
	 * Check if user is authorized for action
	 *
	 * @param	string	$auth 	Submitted authorization code
	 * @return	void
	 */
	private function check_auth( $auth )
	{
		$auth = md5( $auth );

		if( $auth !== $this->auth ) {
			wp_send_json_error( 'You are not authorized for this action' );
			die;
		}
	}

}


Rest_Endpoint::_instance();