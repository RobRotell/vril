<?php


namespace Cine\Model;


use DateTime;
use Exception;
use Vril_Utility;


defined( 'ABSPATH' ) || exit;


class Search_Result
{
	public $id			= null;
	public $title		= null;
	public $description	= null;

	public $year 		= null;
	
	public $poster 		= null;

	public $added 		= false;
	public $to_watch 	= false;
	
	public $post_id 	= null;


	/**
	 * Create search result from TMDB data
	 *
	 * @param	object 	$data 	TMDB movie data
	 * @return 	void
	 */
	public function __construct( object $data )
	{
		if( !isset( $data->id ) || empty( $data->id ) ) {
			throw new Exception( 'Missing ID for search result' );
		} elseif( !isset( $data->title ) || empty( $data->title ) ) {
			throw new Exception( 'Missing title for search result' );
		}

		$this->id 			= Vril_Utility::convert_to_int( $data->id );
		$this->title 		= Vril_Utility::sanitize_var( $data->title );
		$this->description 	= wp_trim_words( 
			Vril_Utility::sanitize_var( $data->overview ), 
			20, 
			' [...]' 
		);

		// some movies might not have been released yet
		$this->year = ( isset( $data->release_date ) ) 
			? self::get_release_year( $data->release_date ) 
			: 'Not released yet';

		if( isset( $data->poster_path ) && !empty( $data->poster_path ) ) {
			$this->poster = Cine()->tmdb::build_image_url( 154, $data->poster_path );
		}

		$this->maybe_already_added();
	}


	/**
	 * Check that required values are present and prep for response
	 *
	 * @return 	mixed 	Array, if valid search result
	 */
	public function package()
	{
		return (array)$this;
	}


	/**
	 * Format release date and extract year
	 *
	 * @param	string	$date 	Movie release date
	 * @return 	mixed 			String for year, if valid release date
	 */
	private static function get_release_year( string $date )
	{
		$date = Vril_Utility::sanitize_var( $date );
		$date = DateTime::createFromFormat( 'Y-m-d', $date );

		if( !empty( $date ) ) {
			return $date->format( 'Y' );
		}
	}


	/**
	 * Check if movie was previously added to database. If so, get watch status
	 *
	 * @return	void
	 */
	private function maybe_already_added(): void
	{
		$movie_ids = get_posts(
			[
				'meta_key'			=> 'id_tmdb',
				'meta_value'		=> $this->id,
				'post_type'			=> Cine()->core::POST_TYPE,
				'posts_per_page'	=> 1,
				'fields'			=> 'ids',
			]
		);

		// already added? Find movie's watch status
		foreach( $movie_ids as $movie_id ) {
			$this->added	= true;
			$this->post_id	= $movie_id;

			$to_watch = get_field( 'to_watch', $this->post_id );
			$this->to_watch = Vril_Utility::convert_to_bool( $to_watch );
		}
	}

}
