<?php


namespace Cine\Abstracts;


use Exception;
use Cine\Core\Post_Types;
use DateTime;
use WP_Post;
use NumberFormatter;


defined( 'ABSPATH' ) || exit;


abstract class Movie
{
	public int $id;
	public string $title;

	private WP_Post $post;
	private int $tmdb_id;

	public string $synopsis;

	public string $release_date;
	public string $release_year;

	public bool $to_watch;

	public array $directors;
	public array $writers;

	public string $budget;
	public string $box_office;

	public string $website;

	public array $genres;

	public string $image_backdrop_url = '';
	public string $image_poster_url = '';

	public int $rating; 
	public string $runtime;


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
			throw new Exception( 'Movie cannot be constructed from argument.' );
		}
		
		$this->id = $post->ID;
		$this->title = apply_filters( 'the_title', $post->post_title );

		$this->post = $post;
	}


	/**
	 * Get synopsis for movie
	 *
	 * @return 	self
	 */
	public function populate_synopsis(): self
	{
		$this->synopsis = wp_strip_all_tags( apply_filters( 'the_content', $this->post->post_content ) );

		return $this;
	}


	/**
	 * Get TMDb for movie
	 *
	 * @return 	self
	 */
	public function populate_tmdb_id(): self
	{
		$this->tmdb_id = get_field( 'id_tmdb', $this->id );

		return $this;
	}	


	/**
	 * Get watch/to watch status for movie
	 *
	 * @return 	self
	 */
	public function populate_watch_status(): self
	{
		$this->to_watch = get_field( 'to_watch', $this->id );

		return $this;
	}


	/**
	 * Get my ultra official rating for movie
	 *
	 * @return 	self
	 */
	public function populate_rating(): self
	{
		$this->rating = absint( get_field( 'rating', $this->id ) );

		return $this;
	}	


	/**
	 * Get backdrop image for movie
	 *
	 * @return 	self
	 */
	public function populate_image_backdrop(): self
	{
		$backdrop_image_id = get_field( 'backdrop', $this->id );

		if( $backdrop_image_id ) {
			$this->image_backdrop_url = wp_get_attachment_image_url( $backdrop_image_id, 'backdrop' );
		}

		return $this;
	}


	/**
	 * Get poster image for movie
	 *
	 * @return 	self
	 */
	public function populate_image_poster(): self
	{
		$poster_image_id = get_field( 'poster', $this->id );

		if( $poster_image_id ) {
			$this->image_poster_url = wp_get_attachment_image_url( $poster_image_id, 'poster' );
		}

		return $this;
	}				


	/**
	 * Get genre(s) for movie
	 *
	 * @return 	self
	 */
	public function populate_genres(): self
	{
		$this->genres = wp_get_object_terms( 
			$this->id, 
			'genre',
			[
				'fields' => 'id=>name'
			] 
		);

		return $this;
	}


	/**
	 * Get credits (director/writer) for movie
	 *
	 * @return 	self
	 */
	public function populate_credits(): self
	{
		$writers = get_field( 'writer', $this->id );
		if( !empty( $writers = get_field( 'writer', $this->id ) ) ) {
			$writers = explode( ',', $writers );
			$this->writers = array_map( 'trim', $writers );
		}

		$directors = get_field( 'writer', $this->id );
		if( !empty( $directors = get_field( 'director', $this->id ) ) ) {
			$directors = explode( ',', $directors );
			$this->directors = array_map( 'trim', $directors );
		}

		$this->website = esc_url_raw( get_field( 'website', $this->id ) );
		$this->runtime = get_field( 'runtime', $this->id ) ?? '';

		return $this;
	}


	/**
	 * Get release dates for movie
	 *
	 * @return 	self
	 */
	public function populate_dates(): self
	{
		try {
			$release_date = get_field( 'release_date', $this->id );
			$release_datetime = new DateTime( $release_date );

			if( $release_datetime ) {
				$this->release_date = $release_datetime->format( 'Y-m-d' );
				$this->release_year = $release_datetime->format( 'Y' );
			}

		} catch( Exception $e ) {
			// intentionally empty
		}

		return $this;
	}


	/**
	 * Get finances (budget/box office) for movie
	 *
	 * @return 	self
	 */
	public function populate_finances(): self
	{
		$this->budget 		= $this->convert_to_dollar( get_field( 'budget', $this->id ) );
		$this->box_office 	= $this->convert_to_dollar( get_field( 'box_office', $this->id ) );

		return $this;
	}


	/**
	 * Convert value to USD
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
