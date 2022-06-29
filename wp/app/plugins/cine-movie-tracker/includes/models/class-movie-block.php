<?php


namespace Cine\Models;


use WP_Post;
use DateTime;
use NumberFormatter;
use Vril_Utility;
use Exception;
use Cine\Core\Post_Types;


defined( 'ABSPATH' ) || exit;


class Movie_Block
{
	public int $id;
	public string $title;
	public string $synopsis;

	// for internal usage only
	private WP_Post $post;
	private int $tmdb_id;
	
	private ?DateTime $release_datetime;
	public ?int $release_year;
	public ?string $release_date;

	public bool $to_watch;
	public bool $watched;

	public string $website;
	public string $director;
	public string $writer;
	public string $budget;
	public string $box_office;
	
	public string $backdrop;
	public string $poster;


	/**
	 * Create movie block from post ID or post
	 *
	 * @throws 	Exception 	Invalid post
	 * @param	int|WP_Post	$arg	Either post ID or WP_Post
	 */
	public function __construct( int|WP_Post $post ) 
	{
		if( is_int( $post ) ) {
			$post = get_post( $post );
		}

		if( !$post || $post->post_type !== Post_Types::POST_TYPE ) {
			throw new Exception( 'Movie block cannot be constructed from argument' );
		}

		$this->post 	= $post;
		$this->id 		= $post->ID;
		$this->title 	= $post->post_title;
		$this->synopsis	= $post->post_content;

		$this
			->populate_meta_props()
			->populate_release_props()
			->populate_image_props();
	}


	/**
	 * Populate common data props to display on frontend
	 *
	 * @return 	self
	 */
	public function populate_meta_props(): self
	{
		$this->tmdb_id 		= get_field( 'id_tmdb', $this->id );

		$this->to_watch 	= Vril_Utility::convert_to_bool( get_field( 'to_watch', $this->id ) );
		$this->watched 		= !$this->to_watch;

		$this->writer		= get_field( 'writer', $this->id );
		$this->director 	= get_field( 'director', $this->id );

		$this->budget 		= $this->convert_to_dollar( get_field( 'budget', $this->id ) );
		$this->box_office 	= $this->convert_to_dollar( get_field( 'box_office', $this->id ) );
		
		$this->website 		= esc_url_raw( get_field( 'website', $this->id ) );

		return $this;
	}


	/**
	 * Populate props related to movie's release
	 *
	 * @return 	self
	 */
	public function populate_release_props(): self
	{
		try {
			$release_date = get_field( 'release_date', $this->id );
			$this->release_datetime = new DateTime( $release_date );

			if( $this->release_datetime ) {
				$this->release_year = $this->release_datetime->format( 'Y' );
				$this->release_date = $this->release_datetime->format( 'Y-m-d' );
			}

		} catch( Exception $e ) {
			// intentionally empty
		}

		return $this;
	}


	/**
	 * Populate props related to images
	 *
	 * @return 	self
	 */
	public function populate_image_props(): self
	{
		$poster_id = get_post_thumbnail_id( $this->id );
		if( !empty( $post_id ) ) {
			$this->poster	= wp_get_attachment_image_url( $poster_id, 'large' );
		}

		// if no backdrop exists, fall back to poster
		$backdrop_id = get_field( 'backdrop', $this->id );
		if( empty( $backdrop_id ) && !empty( $poster_id ) ) {
			$backdrop_id = $poster_id;
		}
		
		if( !empty( $backdrop_id ) ) {
			$this->backdrop = wp_get_attachment_image_url( $backdrop_id, 'backdrop_small' );
		}

		return $this;
	}


	/**
	 * Convert value to USD amount
	 *
	 * @param	int	$value		Original USD amount
	 * @return	string|false	String, if could be converted to original USD amount; otherwise false
	 */
	public function convert_to_dollar( int $value ): string|false 
	{
		if( !empty( $value ) && class_exists( 'NumberFormatter' ) ) {
			$format = new NumberFormatter( 'en_US', NumberFormatter::DECIMAL );

			return sprintf( '$%s', $format->format( $value ) );
		}

		return false;
	}

}
