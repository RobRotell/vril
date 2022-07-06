<?php


namespace Cine\Models;


use Cine\Controllers\Genres;
use Cine\Controllers\Helpers;
use Cine\Controllers\TMDb;


defined( 'ABSPATH' ) || exit;


class Movie_To_Add
{
	public int $id_tmdb; 

	// metadata directly from TMDb
	private array $details;
	private array $credits;

	public array $genre_term_ids;

	

	/**
	 * Create movie post from TMDb
	 *
	 * @param	int		$id_tmdb	Movie's TMDb ID
	 */
	public function __construct( int $id_tmdb )
	{
		$this->id_tmdb 	= absint( $id_tmdb );
	}


	/**
	 * Grab and process metadata for movie from TMDb
	 *
	 * @return 	self
	 */
	public function process_data_from_tmdb(): self
	{
		$this
			->process_details()
			->process_credits()
			->process_genres()
			// ->process_production_companies()
			->process_images();

		return $this;
	}


	/**
	 * Grab and process movie details
	 *
	 * @return 	self
	 */
	private function process_details(): self
	{
		$details = (array)TMDb::fetch_movie_details( $this->id_tmdb );

		$this->title		= sanitize_text_field( $details['title'] ?? '' );
		$this->synopsis 	= sanitize_text_field( $details['overview'] ?? '' );
		$this->tagline 		= sanitize_text_field( $details['tagline'] ?? '' );
		$this->release_date = sanitize_text_field( $details['release_date'] ?? '' );
		$this->website 		= esc_url_raw( $details['homepage'] ?? '' );
		$this->runtime 		= absint( $details['runtime'] ?? 0 );
		$this->budget 		= absint( $details['budget'] ?? 0 );
		$this->box_office 	= absint( $details['revenue'] ?? 0 );

		// stash for later
		$this->details = $details;

		return $this;
	}


	/**
	 * Grab and process movie details
	 *
	 * @return 	self
	 */
	private function process_credits(): self
	{
		$credits = (array)TMDb::fetch_movie_credits( $this->id_tmdb );

		// todo — maybe extend to $credits['cast'] in future
		foreach( $credits['crew'] as $team_member ) {
			if( 'Director' === $team_member->job ) {
				$this->directors[] = $team_member->name;
			} elseif( 'Writer' === $team_member->job ) {
				$this->writers[] = $team_member->name;
			}
		}

		// stash for later
		$this->credits = $credits;

		return $this;
	}


	/**
	 * Process genres and assign taxonomy terms
	 *
	 * @return 	self
	 */
	private function process_genres(): self
	{
		$genres = $this->details['genres'] ?? [];

		foreach( $genres as $genre ) {
			$genre = (array)$genre;

			// preexisting term?
			$term_id = Genres::get_genre_by_tmdb_id( $genre['id'] );
			
			// if no preexisting term, create it
			if( empty( $term_id ) ) {
				$term_id = Genres::create_genre_by_tmdb_data( $genre['id'], $genre['name'] );
			}

			$this->genre_term_ids[] = $term_id;
		}

		return $this;
	}


	/**
	 * Grab and process movie details
	 *
	 * @return 	self
	 */
	private function process_images(): self
	{
		$backdrop_path	= $this->details['backdrop_path'] ?? '';
		$poster_path 	= $this->details['poster_path'] ?? '';

		if( !empty( $backdrop_path ) ) {
			$this->image_id_backdrop = $this->save_image_from_tmdb_path( $backdrop_path, 'backdrop' );
		}

		if( !empty( $poster_path ) ) {
			$this->image_id_poster = $this->save_image_from_tmdb_path( $poster_path, 'poster' );
		}

		return $this;
	}	


	/**
	 * Retrieve, optimize, and save image file from TMDb
	 *
	 * @param	string 	$path 	URL for image
	 * @param 	string 	$type	Image type (e.g. "poster" vs "backdrop")
	 * 
	 * @return 	int 			Attachment ID
	 */
	private function save_image_from_tmdb_path( string $path, string $type ): int
	{
		// dependencies
		global $wp_filesystem;
		Helpers::load_image_file_system();

		$attachment_id = null;

		$path_for_url = sanitize_text_field( $path );
		$width = ( 'poster' === $type ) ? '780' : '1280';

		$url = TMDb::build_image_url( $width, $path_for_url );
		
		// todo — throw exception
		$tmp_file = download_url( $url );
		if( !empty( $tmp_file ) ) {

			// optimize image
			$tmp_data = $wp_filesystem->get_contents( $tmp_file );
			$optimized_data = Cine()->tinify->optimize_image_from_data( $tmp_data );
			$wp_filesystem->put_contents( $tmp_file, $optimized_data );

			$file_name = sprintf(
				'%s-%s.%s',
				sanitize_title( $this->title ),
				$type,
				pathinfo( $url, PATHINFO_EXTENSION )
			);

			$file = [
				'name' 		=> $file_name,
				'tmp_name'	=> $tmp_file,
			];

			// add image to media library
			$attachment_id = media_handle_sideload( $file, 0 );

			// add original path to metadata (to avoid re-downloading same image)
			update_post_meta( $attachment_id, '_tmdb_path', $path );
		}

		if( is_file( $tmp_file ) ) {
			unlink( $tmp_file );
		}

		return $attachment_id;
	}

}
