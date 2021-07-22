<?php


namespace Cine;


use Vril_Utility;
use DateTime;


defined( 'ABSPATH' ) || exit;


class New_Movie
{
	// WP-native
	public $post_id 	= null;
	public $title		= null;
	public $content 	= null;
	public $thumbnail 	= null;
	
	// taxonomy
	public $genres 		= [];
	
	// custom fields
	public $tmdb_id		= null;
	public $backdrop 	= null;
	public $box_office 	= null;
	public $budget		= null;
	public $rating		= null;
	public $release 	= null;
	public $website		= null;
	public $to_watch 	= false;

	// credits
	public $director 	= [];
	public $writer 		= [];


	/**
	 * Build new movie
	 *
	 * @param	object 	$data 	Data from TMDB
	 * @return 	void
	 */
	public function __construct( object $data )
	{
		$this->title 		= Vril_Utility::sanitize_var( $data->title );
		$this->content 		= Vril_Utility::sanitize_var( $data->overview );
		
		// custom fields
		$this->tmdb_id		= Vril_Utility::convert_to_int( $data->id );
		$this->box_office 	= Vril_Utility::convert_to_int( $data->revenue );
		$this->budget 		= Vril_Utility::convert_to_int( $data->budget );
		$this->release 		= Vril_Utility::sanitize_var( $data->release_date );
		$this->website 		= esc_url_raw( $data->homepage );

		// assign genres
		$this->genres = self::check_for_genres( $data->genres );

		// check for preexisting movie posts matching this ID
		$this->post_id = self::check_for_preexisting_movie( $this->tmdb_id, $this->title );

		// process images
		$this->process_images( $data->poster_path, $data->backdrop_path );
	}


	/**
	 * Clean up genres for movie
	 *
	 * @param	array 	$genres_raw 	Genres from TMDB
	 * @return 	array 					Genre names
	 */
	private static function check_for_genres( array $genres_raw )
	{
		$genres = [];
		foreach( $genres_raw as $genre_raw ) {
			if( property_exists( $genre_raw, 'name' ) ) {
				$genres[] = Vril_Utility::sanitize_var( $genre_raw->name );
			}
		}

		return $genres;
	}


	/**
	 * Process and fetch images for movie
	 *
	 * @param	string	$poster_path 	Poster image path on TMDB
	 * @param 	string 	$backdrop_path 	(larger) backdrop image path on TMDB
	 * 
	 * @return 	self
	 */
	private function process_images( string $poster_path, string $backdrop_path ): self
	{
		$poster_img_id = null;
		$backdrop_img_id = null;

		// if movie is preexisting, grab current images
		if( !empty( $this->post_id ) ) {

			// grab poster and check that poster path's metadata matches
			$current_poster_id = (int)get_post_thumbnail_id( $this->post_id );
			if( $poster_path === get_post_meta( $current_poster_id, '_tmdb_path', true ) ) {
				$poster_img_id = $current_poster_id;

			// delete current thumbnail and replace with new thumbnail
			} else {
				wp_delete_attachment( $current_poster_id, true );
			}

			// grab backdrop and check that backdrop's path's metadata matches
			$current_backdrop_id = (int)get_field( 'backdrop', $this->post_id );
			if( $backdrop_path === get_post_meta( $current_backdrop_id, '_tmdb_path', true ) ) {
				$backdrop_img_id = $current_backdrop_id;
			
			// delete current backdrop and replace with new backdrop
			} else {
				wp_delete_attachment( $current_backdrop_id, true );
			}			
		}

		// at this point, assume movie is new and grab new images (or deleted/updated above)
		if( empty( $poster_img_id ) && !empty( $poster_path ) ) {
			$poster_img_id = $this->grab_image( $poster_path, 'poster' );
		}

		if( empty( $backdrop_img_id ) && !empty( $backdrop_path ) ) {
			$backdrop_img_id = $this->grab_image( $backdrop_path, 'backdrop' );
		}

		$this->thumbnail	= $poster_img_id;
		$this->backdrop		= $backdrop_img_id;

		return $this;
	}


	/**
	 * Fetch image from TMDB
	 *
	 * @param	string	$path 	Image path on TMDB
	 * @param 	string 	$case 	Image case, used for sizing
	 * 
	 * @return 	mixed 			Attachment ID on successful download; otherwise null
	 */
	private function grab_image( string $path, string $case = 'poster' )
	{
		if( !function_exists( 'media_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$attachment_id = null;

		$path_for_url = Vril_Utility::sanitize_var( $path );
		$width = ( $case === 'poster' ) ? '780' : '1280';

		$url = Cine()->tmdb::build_image_url( $width, $path_for_url );
		$tmp = download_url( $url );

		if( !empty( $tmp ) ) {
			$file_name = sprintf(
				'%s-%s.%s',
				sanitize_title( $this->title ),
				$case,
				pathinfo( $url, PATHINFO_EXTENSION )
			);

			$file = [
				'name' 		=> $file_name,
				'tmp_name'	=> $tmp,
			];

			// add image to media library
			$attachment_id = media_handle_sideload( $file, 0 );

			// add original path to metadata (to avoid re-downloading same image)
			update_post_meta( $attachment_id, '_tmdb_path', $path );
		}

		if( is_file( $tmp ) ) {
			unlink( $tmp );
		}

		return $attachment_id;
	}


	/**
	 * Check if movie was already added to database
	 *
	 * @param 	int 	$tmdb_id 	Movie ID in TMDB
	 * @param	string	$title 		Movie title
	 * 
	 * @return	mixed 				Post ID if matching post found; otherwise, false
	 */
	private static function check_for_preexisting_movie( int $tmdb_id, string $title )
	{
		$movies = get_posts(
			[
				'post_type'		=> 'movie',
				'post_status'	=> 'any',
				'meta_key'		=> 'id_tmdb',
				'meta_value'	=> $tmdb_id
			]
		);

		// TODO — what happens if more than one result?
		foreach( $movies as $movie ) {
			if( $title === $movie->post_title ) {
				return $movie->ID;
			}
		}

		return false;
	}


	/**
	 * Grab specific credits from raw data
	 *
	 * @param	object 	$credits 	Credits
	 * @return 	self
	 */
	public function set_credits( object $credits ): self
	{
		if( !property_exists( $credits, 'crew' ) ) {
			return false;
		}

		$directors	= [];
		$writers	= [];
		
		foreach( $credits->crew as $crew_member ) {
			$department = $crew_member->department;
			$job = $crew_member->job;

			if( 'Directing' === $department && in_array( $job, [ 'Director', 'Directed by' ] ) ) {
				$directors[] = Vril_Utility::sanitize_var( $crew_member->name );
			} elseif( 'Writing' === $department && ( 'Writer' === $job || strpos( $job, 'Screenplay' ) !== false ) ) {
				$writers[] = Vril_Utility::sanitize_var( $crew_member->name );
			}
		}

		$this->director = implode( ', ', array_unique( $directors ) );
		$this->writer 	= implode( ', ', array_unique( $writers ) );

		return $this;
	}


	/**
	 * Already watched movie? Or saving for later
	 *
	 * @param	boolean	$status 	Watch status
	 * @return	self
	 */
	public function set_watch_status( bool $status = false ): self
	{
		$this->to_watch = $status;

		return $this;
	}


	/**
	 * Save movie as post
	 *
	 * @return 	int 	Post ID
	 */
	public function save_as_post(): int
	{
		$post_data = [
			'ID'			=> $this->post_id ?: 0,
			'post_content'	=> $this->content,
			'post_status'	=> 'publish',
			'post_title' 	=> $this->title,
			'post_type'		=> Cine()->core::POST_TYPE,
			'meta_input'	=> [
				'backdrop' 		=> $this->backdrop,
				'box_office' 	=> $this->box_office,
				'budget' 		=> $this->budget,
				'director' 		=> $this->director,
				'id_tmdb' 		=> $this->tmdb_id,
				'rating' 		=> $this->rating,
				'release_date'	=> $this->release,
				'to_watch' 		=> $this->to_watch,
				'website' 		=> $this->website,
				'writer' 		=> $this->writer,
			]
		];

		// add movie as post
		$this->post_id = wp_insert_post( $post_data );

		// set poster as thumbnail
		set_post_thumbnail( $this->post_id, $this->thumbnail );

		// set custom fields
		// update_field( 'id_tmdb', 		$this->tmdb_id, $this->post_id );
		// update_field( 'to_watch', 		$this->to_watch, $this->post_id );
		// update_field( 'director', 		$this->director, $this->post_id );
		// update_field( 'writer', 			$this->writer, $this->post_id );
		// update_field( 'release_date',	$this->release, $this->post_id );
		// update_field( 'budget', 			$this->budget, $this->post_id );
		// update_field( 'rating', 			$this->rating, $this->post_id );
		// update_field( 'box_office', 		$this->box_office, $this->post_id );
		// update_field( 'website', 		$this->website, $this->post_id );
		// update_field( 'backdrop', 		$this->backdrop, $this->post_id );

		// find genres
		$genre_ids = [];
		foreach( $this->genres as $genre ) {
			$genre_ids[] = Cine()->helper::get_genre_by_name( $genre, true );
		}

		// assign genres
		if( !empty( $genre_ids ) ) {
			wp_set_object_terms( $this->post_id, $genre_ids, Cine()->core::TAXONOMY );
		}

		// return to post ID to endpoint
		return $this->post_id;
	}

}
