<?php

namespace Loa_Article_Tracker;


defined( 'ABSPATH' ) || exit;


class Helpers
{
	/**
	 * Get all article tags
	 *
	 * @return 	array 	Tags
	 */
	public static function get_tags()
	{
		$transient_name = 'loa_article_tracker_all_terms';

		$terms = get_transient( $transient_name );
		if( empty( $terms ) ) {
			$terms = get_terms(
				[
					'taxonomy'		=> Core::TAXONOMY,
					'hide_empty'	=> false,
					'fields'		=> 'id=>name'
				]
			);			

			set_transient( $transient_name, $terms, 604800 );
		}

		return $terms;
	}


	/**
	 * Get all (optionally read) articles
	 *
	 * @param 	bool 	$read	Include read articles
	 * @return 	array 			Articles
	 */
	public static function get_articles( bool $read = false )
	{
		$transient_name = 'loa_article_tracker_all_articles';
		
		$articles = get_transient( $transient_name );
		if( empty( $articles ) ) {
			$args = [
				'post_type'         => Core::POSTTYPE,
				'posts_per_page'    => -1,
			];

			if( !$read ) {
				$args['meta_query'] = [
					[
						'key'		=> 'article_date_read',
						'value'		=> '',
						'compare'	=> 'NOT EXISTS'
					]
				];
			}

			$articles = get_posts( $args );

			require_once( LoaArticleTracker()->plugin_inc_path . '/models/class-article.php' );
			$articles = [];
			foreach( $posts as $post ) {
				$articles[] = ( new Article( $post ) )->get_atts();
			}

			set_transient( $transient_name, $articles, 604800 );
		}
		
		return $articles;
	}


	/**
	 * Get count for read articles
	 *
	 * @return 	int 	Total read articles
	 */
	public static function get_total_read()
	{
		$read_article_ids = get_posts(
			[
				'fields'			=> 'ids',
				'post_type'			=> Core::POSTTYPE,
				'posts_per_page'	=> -1,
				'meta_query'		=> [
					[
						'key'		=> 'article_is_read',
						'compare'	=> 'LIKE',
						'value'		=> true
					]
				]
			]
		);

		return count( $read_article_ids );
	}


	/**
	 * Add article
	 *
	 * @param	string	$url 		Article URL
	 * @param 	mixed 	$tags 		String or array of tag term IDs
	 * @param 	bool 	$read 		True, if article has been read	
	 * @param 	bool 	$favorite 	True, if article has been favorited
	 * 
	 * @return 	int 				Article post ID
	 */
	public static function add_article( string $url, $tags = '', bool $read = false, bool $favorite = false )
	{
		$url = filter_var( $url, FILTER_SANITIZE_URL );
		if( !$url ) {
			return false;
		}

		$url 	= str_replace( 'http://', 'https://', $url );
		$tags 	= self::sanitize_tag_ids( $tags );

		// check if article has already been saved
		$preexisting_articles = get_posts(
			[
				'fields' 		=> 'ids',
				'meta_key'		=> 'article_url',
				'meta_value'	=> $url,
				'post_type'		=> Core::POSTTYPE,
			]
		);

		// if matches, just update article's read status and categories
		if( !empty( $preexisting_articles ) ) {
			$article_id = $preexisting_articles[0];

			self::update_article_status( $article_id, $read );
			self::update_article_tags( $article_id, $tags );

			return $article_id;
		}

		// try to retrieve remote page to get page's title
		$title = $url;
		$response = wp_safe_remote_get( $url );
		if( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = wp_remote_retrieve_body( $response );

			$title_tag_start 	= strpos( $body, '<title>' );
			$title_tag_end 		= strpos( $body, '</title>' );

			if( !empty( $title_tag_start ) && !empty( $title_tag_end ) ) {
				$title_start = $title_tag_start + 7; // strlen( '<title>' )

				$title = substr( $body, $title_start, ( $title_tag_end - $title_start ) );
				$title = sanitize_text_field( $title );
	
				if( strlen( $title ) > 20 ) {
					$title = sprintf( '%s [...]', substr( $title, 0, 20 ) );
				}
			}
		}

		// prep to save new article post
		$args = [
			'post_title'	=> $title,
			'post_type'		=> Core::POSTTYPE,
			'post_status'	=> 'publish',
			'meta_input'	=> [
				'article_date_added'	=> date( 'Y-m-d', time() ),
				'article_url'			=> $url
			]
		];

		// pass article read status
		if( $read ) {
			$args['meta_input']['article_is_read'] = true;
		}

		// pass article favorite status
		if( $favorite ) {
			$args['meta_input']['article_is_favorite'] = true;
		}

		// create new post
		$article_id = wp_insert_post( $args );

		// if tags, add those
		if( !empty( $tags ) && !empty( $article_id ) ) {
			wp_set_object_terms( $article_id, $tags, Core::TAXONOMY );
		}

		do_action( 'loa_article_tracker_added_article' );

		return $article_id;
	}


	/**
	 * Update an article's read status
	 *
	 * @param	int 	$post_id	Article's post ID
	 * @param 	bool 	$status 	True, if article has been read
	 * 
	 * @return 	void
	 */
	public static function update_article_status( int $post_id, bool $status )
	{
		$current_status = get_field( 'article_date_read', $post_id );

		if( $status !== $current_status ) {
			update_field( 'article_date_read', $post_id, $status );
			do_action( 'loa_article_tracker_updated_article' );
		}
	}


	/**
	 * Update an article's tags
	 *
	 * @param	int 	$post_id	Article's post ID
	 * @param 	mixed 	$tags 		String or array of tag term IDs
	 * 
	 * @return 	void
	 */
	public static function update_article_tags( int $post_id, $tags = '' )
	{
		$tags = self::sanitize_tag_ids( $tags );

		$current_tags = wp_get_object_terms( 
			$post_id, 
			Core::TAXONOMY,
			[
				'fields' => 'ids'
			]
		);

		if( $tags !== $current_tags ) {
			wp_set_post_terms( $post_id, $tags, Core::TAXONOMY );
			do_action( 'loa_article_tracker_updated_article' );
		}
	}	


	/**
	 * Convert argument into an array of tag term IDs
	 *
	 * @param	mixed 	$tags 	String or array of tag IDs
	 * @return 	array 
	 */
	public static function sanitize_tag_ids( $args = '' )
	{
		$tags = [];

		if( is_string( $args ) ) {
			$args = explode( ',', $args );
		}

		if( is_array( $args ) ) {
			foreach( $args as $arg ) {
				$arg = absint( trim( $arg ) );

				if( !empty( $arg ) ) {
					$tags[] = $arg;
				}
			}
		}

		$tags = array_unique( array_filter( $tags ) );

		return $tags;
	}


	/**
	 * Clean, format, and hash submitted authorization key
	 *
	 * @param	string	$key 	Raw key
	 * @return 	string 			Formatted key
	 */
	public static function sanitize_auth_key( string $key )
	{
		$key = sanitize_text_field( $key );
		$key = strtolower( trim( $key ) );
		$key = preg_replace( '/[^A-Za-z0-9]/', '', $key );
		$key = md5( $key );

		return $key;
	}


	/**
	 * Sanitize article URL
	 *
	 * @param	string	$url 	Raw article URL
	 * @return 	string
	 */
	public static function sanitize_url( string $url )
	{
		return filter_var( $url, FILTER_SANITIZE_URL );
	}

}