<?php


namespace Cine\Models;


use WP_Post;
use DateTime;
use NumberFormatter;
use Vril_Utility;
use Exception;
use Cine\Core\Post_Types;
use Cine\Abstracts\Movie;
use Cine\Model\Production_Company;


defined( 'ABSPATH' ) || exit;


class Frontend_Movie extends Movie
{
	// for internal usage only
	public int 		$id_post;
	private WP_Post	$post; 


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

		if( !$post || $post->post_type !== Post_Types::POST_TYPE_KEY ) {
			throw new Exception( 'Movie cannot be constructed from argument' );
		}

		$this->id_post 	= $post->ID;
		$this->post 	= $post;

		$this->title 	= apply_filters( 'the_title', $post->post_title );
		// $this->synopsis	= apply_filters( 'the_content', $post->post_content );

		// $this
		// 	->populate_meta_props()
		// 	->populate_credits()
		// 	->populate_release_props()
		// 	->populate_image_props();
	}


	/**
	 * Populate common data props to display on frontend
	 *
	 * @return 	self
	 */
	public function populate_meta_props(): self
	{
		$this->id_tmdb 		= get_field( 'id_tmdb', $this->id_post );
		
		$this->to_watch 	= Vril_Utility::convert_to_bool( get_field( 'to_watch', $this->id_post ) );
		$this->watched 		= !$this->to_watch;
		
		$this->website 		= esc_url_raw( get_field( 'website', $this->id_post ) );
		$this->runtime 		= get_field( 'runtime', $this->id_post );

		$this->budget 		= $this->convert_to_dollar( get_field( 'budget', $this->id_post ) );
		$this->box_office 	= $this->convert_to_dollar( get_field( 'box_office', $this->id_post ) );

		return $this;
	}


	/**
	 * Populate props related to movie credits
	 *
	 * @return 	self
	 */
	public function populate_credits(): self
	{
		$writers = get_field( 'writer', $this->id_post );
		if( !empty( $writers = get_field( 'writer', $this->id_post ) ) ) {
			$writers = explode( ',', $writers );
			$this->writers = array_map( 'trim', $writers );
		}

		$directors = get_field( 'writer', $this->id_post );
		if( !empty( $directors = get_field( 'director', $this->id_post ) ) ) {
			$directors = explode( ',', $directors );
			$this->directors = array_map( 'trim', $directors );
		}

		// $terms = wp_get_object_terms( $this->id_post, Production_Companies::TAXONOMY_KEY );
		// foreach( $terms as $term ) {
		// 	$this->production_companies[] = new Production_Company( $term );
		// }
		
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
			$release_date = get_field( 'release_date', $this->id_post );
			$this->release_datetime = new DateTime( $release_date );

			if( $this->release_datetime ) {
				$this->release_date = $this->release_datetime->format( 'Y-m-d' );
				$this->release_year = $this->release_datetime->format( 'Y' );
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
		$poster_id = get_post_thumbnail_id( $this->id_post );
		if( !empty( $post_id ) ) {
			$this->poster	= wp_get_attachment_image_url( $poster_id, 'large' );
		}

		// if no backdrop exists, fall back to poster
		$backdrop_id = get_field( 'backdrop', $this->id_post );
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
