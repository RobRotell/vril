<?php


namespace Cine\Controllers;


use Cine\Core\Taxonomy_Genres;
use WP_Term;


defined( 'ABSPATH' ) || exit;


class Genres
{
	/**
	 * Get preexisting genre term by ID
	 *
	 * @param	int		$id		Term ID
	 * @return 	WP_Term|false	Term if match found; otherwise, false  
	 */
	public static function get_genre_by_id( int $id ): WP_Term|false
	{
		return get_term_by( 'term_id', $id, Taxonomy_Genres::TAXONOMY_KEY );
	}


	/**
	 * Get preexisting genre term by TMDb ID
	 *
	 * @param	int		$id		TMDb ID
	 * @return 	WP_Term|false	Term if match found; otherwise, false  
	 */
	public static function get_genre_by_tmdb_id( int $id ): WP_Term|false
	{
		$args = [
			'taxonomy' 		=> Taxonomy_Genres::TAXONOMY_KEY,
			'hide_empty'	=> false,
			'meta_key'		=> 'id_tmdb',
			'meta_value'	=> (string)$id,
			'meta_compare'	=> '='
		];

		$terms = get_terms( $args );
		if( !empty( $terms ) ) {
			return $terms[0];
		}

		return false;
	}
	

	/**
	 * Create new genre term based on TMDb data
	 *
	 * @param	int 	$id 	TMDb genre ID
	 * @param 	string 	$name 	TMDb genre name
	 * 
	 * @return 	WP_Term|false	WP_Term; otherwise, false if failed to create
	 */
	public static function create_genre_by_tmdb_data( int $id, string $name ): int|false
	{
		// double-check for preexisting term
		// todo — check if names match?
		if( !empty( $preexisting = self::get_genre_by_tmdb_id( $id ) ) ) {
			return $preexisting->term_id;
		}

		$term_ids = wp_insert_term( $name, Taxonomy_Genres::TAXONOMY_KEY );

		/**
		 * On successful term creation, WP will return an array with term ID.
		 * 
		 * Earlier versions of Vril may have genre terms that are missing TMDb IDs. If term with same name is found, then WP will return a WP_Error. In these cases, we'll update the genre term to contain the TMDb term ID to help with future queries.
		 */
		if( is_array( $term_ids ) ) {
			$term_id = $term_ids['term_id'];

			update_term_meta( $term_id, 'id_tmdb', $id );

			return get_term( $term_id );

		} elseif( is_wp_error( $term_ids ) ) {
			if( in_array( 'term_exists', $term_ids->get_error_codes() ) ) {
				$term = get_term_by( 'name', $name, Taxonomy_Genres::TAXONOMY_KEY );

				if( $term ) {
					update_term_meta( $term->term_id, 'id_tmdb', $id );

					return $term;
				}
			}
		}

		return false;
	}

}
