<?php


namespace Cine\Model;

use Cine\Controllers\TMDb;
use Cine\Core\Post_Types;
use DateTime;
use Exception;
use Vril_Utility;


defined( 'ABSPATH' ) || exit;


class TMDb_Movie_Result
{
	private ?int 	$wp_post_id; 

	public int 		$id_tmdb; 
	public string 	$title;	
	public string 	$synopsis;

	public ?string 	$release_date;
	public ?int 	$release_year;
	
	public ?string 	$poster; 	

	public bool 	$added = false;
	public ?bool 	$to_watch = null; 
	

	/**
	 * Create search result from TMDb data
	 *
	 * @param	object 	$data 	TMDb movie data
	 */
	public function __construct( object $data )
	{
		$this->id_tmdb 	= absint( $data->id );
		$this->title 	= TMDb::sanitize_convert_string( $data->title );
		$this->synopsis = TMDb::sanitize_convert_string( $data->overview );

		$release_date = $data->release_date ?? '';
		if( !empty( $release_date ) ) {
			try { 
				$release_date = new DateTime( $data->release_date );
	
				$this->release_date = $release_date->format( 'Y-m-d' );
				$this->release_year = $release_date->format( 'Y' );
	
			} catch( Exception $e ) {
				// intentionally empty
			}
		}

		$poster_path = $data->poster_path ?? '';
		if( !empty( $poster_path ) ) {
			$this->poster = TMDb::build_image_url( 154, $poster_path );
		}

		$this->check_if_already_added();
	}


	/**
	 * Check if movie was already added to Cine and if so, watch status
	 *
	 * @return	self
	 */
	private function check_if_already_added(): self
	{
		$movie_ids = get_posts(
			[
				'meta_key'			=> 'id_tmdb',
				'meta_value'		=> $this->id_tmdb,
				'post_type'			=> Post_Types::POST_TYPE_KEY,
				'posts_per_page'	=> 1,
				'fields'			=> 'ids',
			]
		);

		// already added? Find movie's watch status
		foreach( $movie_ids as $movie_id ) {
			$this->added		= true;
			$this->wp_post_id	= $movie_id;

			$to_watch = get_field( 'to_watch', $this->wp_post_id );
			$this->to_watch = Vril_Utility::convert_to_bool( $to_watch );
		}

		return $this;
	}

}
