<?php


namespace Cine\Model;


use WP_Term;
use Cine\Core\Taxonomies\Production_Companies;


defined( 'ABSPATH' ) || exit;


class Production_Company
{
	public $taxonomy = Production_Companies::TAXONOMY_KEY;

	public int $id_term;
	public WP_Term $term;
	
	public string $title;

	public int $id_tmdb;
	public string $origin_country;

	public ?int $thumbnail_id;
		

	/**
	 * Create production company from term
	 *
	 * @param 	WP_Term		$term	Production company term
	 */
	public function __construct( WP_Term $term )
	{
		if( self::$taxonomy !== $term->taxonomy ) {
			throw new Exception( sprintf( 'Taxonomy for term must be: "%s"', self::$taxonomy ) );
		}

		$this->term 	= $term;
		$this->id_term 	= $term->taxonomy_term_id;
		$this->title 	= $term->name;

		$this->id_tmdb 	= get_field( 'id_tmdb', $term );
		$this->origin_country = get_field( 'origin_country', $term );

		



		$this->tmdb_id 	= absint( $data->id );
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
				'meta_value'		=> $this->tmdb_id,
				'post_type'			=> Post_Types::POST_TYPE,
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
