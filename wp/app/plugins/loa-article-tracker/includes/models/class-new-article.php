<?php


namespace Loa\Model;


use Vril_Utility;
use DateTime;


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
		$this->url = esc_url_raw( $url );

		$this
			->check_for_preexisting_article() // have we already added this article?
			->set_post_meta();
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
		$this->tags = [];

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
				'post_type'		=> Loa()->core::POST_TYPE,
				'post_status'	=> 'any',
				'meta_key'		=> 'article_url',
				'meta_value'	=> $this->url
			]
		);

		foreach( $articles as $article ) {

			// double-check that field matches
			if( $this->url === get_field( 'article_url', $article->ID ) ) {
				$this->post_id 		= $article->ID;
				$this->title 		= $article->post_title;
				$this->description 	= $article->post_content;

				$this->read		= get_field( 'article_read', $article->ID );
				$this->favorite	= get_field( 'article_favorite', $article->ID );

				$this->tags	= wp_get_object_terms( 
					$article->ID, 
					Loa()->core::TAXONOMY, 
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
			'post_type'		=> Loa()->core::POST_TYPE,
		];

		// add movie as post
		$this->post_id = wp_insert_post( $post_data );

		// updating ACF separately (fixes issues with meta queries down the road)
		update_field( 'article_read', $this->read );
		update_field( 'article_favorite', $this->favorite );

		// assign genres
		if( !empty( $this->tags ) ) {
			wp_set_object_terms( $this->post_id, $this->tags, Loa()->core::TAXONOMY );
		}

		// return to post ID to endpoint
		return $this->post_id;
	}

}