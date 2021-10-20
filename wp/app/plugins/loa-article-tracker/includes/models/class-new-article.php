<?php


namespace Loa\Model;


use Vril_Utility;
use DateTime;
use Exception;


defined( 'ABSPATH' ) || exit;


class New_Article
{
	// WP-native
	public $post_id 	= null;
	public $title		= '';
	public $description = '';

	// taxonomy
	public $tags 		= [];

	// custom fields
	public $url 		= '';
	public $read 		= false;
	public $favorite 	= false;


	/**
	 * Build new movie
	 *
	 * @param	string 	$url 	Article URL
	 * @return 	void
	 */
	public function __construct( string $url )
	{
		$valid_url = wp_http_validate_url( $url );

		if( !$valid_url ) {
			throw new Exception( sprintf( 'Invalid URL: "%s"', $url ) );

		} else {
			$this->url = $valid_url;
			$this
				->check_for_preexisting_article() // have we already added this article?
				->set_post_meta();			
		}
	}


	/**
	 * Try to extract and set title and description for article based on origin page
	 *
	 * @return 	void
	 */
	private function set_post_meta(): void
	{
		// try to get title and description from page meta
		$meta = Loa()->helper::fetch_meta_for_url( $this->url );
	
		if( !empty( $meta['title'] ) ) {
			$this->title = $meta['title'];
		} else {
			$this->title = $this->url;
		}
	
		if( !empty( $meta['description'] ) ) {
			$this->description = $meta['description'];
		}
	}


	/**
	 * Set tags for article
	 *
	 * @param	array 	$tags 	Article tags
	 * @return 	self
	 */
	public function set_tags( array $tags ): self
	{
		$tags = array_map( 'absint', $tags );
		$tags = array_filter( $tags );

		foreach( $tags as $tag ) {
			if( Loa()->helper::is_tag_id( $tag ) ) {
				$this->tags[] = $tag;
			}
		}

		return $this;
	}


	/**
	 * Set read status
	 *
	 * @param	bool 	$status 	Read status
	 * @return 	self
	 */
	public function set_read_status( bool $status ): self
	{
		$this->read = $status;

		return $this;
	}	


	/**
	 * Set favorite status
	 *
	 * @param	bool 	$status 	Favorite status
	 * @return 	self
	 */
	public function set_favorite_status( bool $status ): self
	{
		$this->favorite = $status;

		return $this;
	}		


	/**
	 * Check if article was already added to database
	 *
	 * @param 	int 	$tmdb_id 	Movie ID in TMDB
	 * @param	string	$title 		Movie title
	 * 
	 * @return	self
	 */
	private function check_for_preexisting_article(): self
	{
		$articles = get_posts(
			[
				'post_type'			=> Loa()->post_types::POST_TYPE,
				'post_status'		=> 'any',
				'posts_per_page'	=> -1,
				'meta_key'			=> 'article_url',
				'meta_value'		=> $this->url
			]
		);

		// double-check that field matches
		foreach( $articles as $article ) {
			if( $this->url === get_field( 'article_url', $article->ID ) ) {
				$this->post_id 		= $article->ID;
				$this->title 		= $article->post_title;
				$this->description 	= $article->post_content;

				$this->read		= get_field( 'article_read', $article->ID );
				$this->favorite	= get_field( 'article_favorite', $article->ID );

				$this->tags	= wp_get_object_terms( 
					$article->ID, 
					Loa()->post_types::TAXONOMY, 
					[
						'fields' => 'tt_ids'
					]
				);

				break;
			}
		}

		return $this;
	}


	/**
	 * Save article as post
	 *
	 * @return 	int 	Post ID
	 */
	public function save_as_post(): int
	{
		$post_data = [
			'ID'			=> $this->post_id ?: 0,
			'post_content'	=> $this->description,
			'post_status'	=> 'publish',
			'post_title' 	=> $this->title,
			'post_type'		=> Loa()->post_types::POST_TYPE,
		];

		// add movie as post
		$this->post_id = wp_insert_post( $post_data );

		// updating ACF separately (fixes issues with meta queries down the road)
		update_field( 'article_read', $this->read, $this->post_id );
		update_field( 'article_favorite', $this->favorite, $this->post_id );
		update_field( 'article_url', esc_url_raw( $this->url ), $this->post_id );

		// assign genres
		if( !empty( $this->tags ) ) {
			wp_set_object_terms( 
				$this->post_id, 
				$this->tags, 
				Loa()->post_types::TAXONOMY 
			);
		}

		// return to post ID to endpoint
		return $this->post_id;
	}

}
