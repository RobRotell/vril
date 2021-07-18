<?php

namespace Movie_Tracker;
use DateTime;

defined( 'ABSPATH' ) || exit;


class Raw_Movie
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


	public function __construct( object $data )
	{
		$this->title 		= Helper::sanitize_var( $data->title );
		$this->content 		= Helper::sanitize_var( $data->overview );
		
		// custom fields
		$this->tmdb_id		= Helper::convert_to_int( $data->id );
		$this->box_office 	= Helper::convert_to_int( $data->revenue );
		$this->budget 		= Helper::convert_to_int( $data->budget );
		$this->website 		= esc_url_raw( $data->homepage );
		$this->release 		= Helper::sanitize_var( $data->release_date );

		// assign genres
		$this->check_for_genres( $data->genres );

		// check for preexisting movie posts matching this ID
		$this->check_for_preexisting_movies();

		// process images
		$this->process_images( $data->poster_path, $data->backdrop_path );

		return $this;
	}


	private function check_for_genres( array $genres_raw )
	{
		$genres = [];
		foreach( $genres_raw as $genre_raw ) {
			if( property_exists( $genre_raw, 'name' ) )
				$genres[] = Helper::sanitize_var( $genre_raw->name );
		}

		$this->genres = $genres;
	}


	private function process_images( $poster_path, $backdrop_path )
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
		if( empty( $poster_img_id ) && !empty( $poster_path ) )
			$poster_img_id = $this->grab_image( $poster_path, 'poster' );

		if( empty( $backdrop_img_id ) && !empty( $backdrop_path ) )
			$backdrop_img_id = $this->grab_image( $backdrop_path, 'backdrop' );

		$this->thumbnail = $poster_img_id;
		$this->backdrop = $backdrop_img_id;
	}


	private function grab_image( string $path, string $case = 'poster' )
	{
		if( !function_exists( 'media_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$path_for_url = urlencode( ltrim( Helper::sanitize_var( $path ), '/' ) );
		$size = ( $case === 'poster' ) ? 'w780' : 'w1280';

		$url = esc_url_raw( sprintf( 'https://image.tmdb.org/t/p/%s/%s', $size, $path_for_url ) );
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

			return $attachment_id;
		}

		if( is_file( $tmp ) )
			unlink( $tmp );
	}


	private function check_for_preexisting_movies()
	{
		$movies = get_posts(
			[
				'post_type'		=> 'movie',
				'post_status'	=> 'any',
				'meta_key'		=> 'id_tmdb',
				'meta_value'	=> $this->tmdb_id
			]
		);

		// TODO — what happens if more than one result?
		foreach( $movies as $movie ) {
			if( $movie->post_title === $this->title ) {
				$this->post_id = $movie->ID;
				return;
			}
		}
	}


	public function set_credits( object $credits )
	{
		if( !property_exists( $credits, 'crew' ) )
			return false;

		$director 	= [];
		$writer 	= [];
		
		foreach( $credits->crew as $crew_member ) {
			$department = $crew_member->department;
			$job = $crew_member->job;

			if( $department === 'Directing' && ( $job === 'Director' || $job === 'Directed by' ) ) {
				$director[] = Helper::sanitize_var( $crew_member->name );
			} elseif( $department === 'Writing' && ( $job === 'Writer' || strpos( $job, 'Screenplay' ) !== false ) ) {
				$writer[] = Helper::sanitize_var( $crew_member->name );
			}
		}

		$this->director = implode( ', ', array_unique( $director ) );
		$this->writer 	= implode( ', ', array_unique( $writer ) );
	}


	/**
	 * Already watched movie? Or saving for later
	 *
	 * @param	boolean	$status 	Watch status
	 * @return	void
	 */
	public function set_watch_status( bool $status = false )
	{
		$this->to_watch = $status;
	}


	public function save_as_post()
	{
		$post = [
			'ID'			=> $this->post_id ?: 0,
			'post_title' 	=> $this->title,
			'post_content'	=> $this->content,
			'post_type'		=> Post_Type::$post_type,
			'post_status'	=> 'publish',
			'meta_input'	=> [
				'id_tmdb' 		=> $this->tmdb_id,
				'to_watch' 		=> $this->to_watch,
				'director' 		=> $this->director,
				'writer' 		=> $this->writer,
				'release_date'	=> $this->release,
				'budget' 		=> $this->budget,
				'rating' 		=> $this->rating,
				'box_office' 	=> $this->box_office,
				'website' 		=> $this->website,
				'backdrop' 		=> $this->backdrop
			]
		];

		// add movie as post
		$post_id = wp_insert_post( $post );
		$this->post_id = $post_id; // in case we need to use later

		// set poster as thumbnail
		set_post_thumbnail( $post_id, $this->thumbnail );

		// set custom fields
		// update_field( 'id_tmdb', 		$this->tmdb_id, $post_id );
		// update_field( 'to_watch', 		$this->to_watch, $post_id );
		// update_field( 'director', 		$this->director, $post_id );
		// update_field( 'writer', 			$this->writer, $post_id );
		// update_field( 'release_date',	$this->release, $post_id );
		// update_field( 'budget', 			$this->budget, $post_id );
		// update_field( 'rating', 			$this->rating, $post_id );
		// update_field( 'box_office', 		$this->box_office, $post_id );
		// update_field( 'website', 		$this->website, $post_id );
		// update_field( 'backdrop', 		$this->backdrop, $post_id );

		// find genres
		$genre_ids = [];
		foreach( $this->genres as $genre ) {
			$genre_ids[] = Helper::get_genre_by_name( $genre, true );
		}

		// assign genres
		if( !empty( $genre_ids ) )
			$result = wp_set_object_terms( $post_id, $genre_ids, Post_Type::$taxonomy );

		// return to post ID to endpoint
		return $post_id;
	}

}
