<?php


namespace Cine\Controller;


use Error;


defined( 'ABSPATH' ) || exit;


class Helper
{
	/**
	 * Singular hashing system for codes
	 *
	 * @param	string	$code 	Code to hash
	 * @param 	bool 	$salted Wrap code to salt?
	 * @return 	string 			Hashed code
	 */
	public static function hash( string $code, bool $salted = true ): string
	{
		if( $salted ) {
			$code = sprintf( 
				'%s%s%s', 
				wp_salt( 'secure_auth' ), 
				$code, 
				wp_salt(), 
			);
		}

		return hash( VRIL_HASH_METHOD, $code );
	}
	
	
	/**
	 * Retrieve genre term by name (and create term if term doesn't exist)
	 *
	 * @param	string	$name 		Term name
	 * @param 	boolean $id_only	Return WP_Term or term ID
	 * 
	 * @return	mixed 				Term|term ID
	 */
	public static function get_genre_by_name( string $name, bool $id_only = false ) 
	{
		$taxonomy = Cine()->core::TAXONOMY;

		$term = get_terms(
			[
				'hide_empty'	=> false,
				'name' 			=> $name,
				'number'		=> 1,
				'taxonomy'		=> $taxonomy,
			]
		);

		// if results, grab first result
		if( !empty( $term ) ) {
			$term = $term[0];

		// if no results, create term
		} else {
			$new_term 	= wp_insert_term( $name, $taxonomy );
			$term 		= get_term( $new_term['term_taxonomy_id'], $taxonomy );
		}

		return ( $id_only ) ? $term->term_id : $term;
	}


	/**
	 * Include model file
	 *
	 * @param	string	$model	Model name
	 * @return 	void
	 */
	public static function load_model( string $model ): void
	{
		$file = sprintf( 
			'%smodels/class-%s.php', 
			Cine()::$plugin_path_inc, 
			sanitize_title( $model ) 
		);

		require_once( $file );
	}


	/**
	 * Convert title for comparision
	 *
	 * @param	string	$title 	Title
	 * @return 	string 			Comparison-friendly title
	 */
	public static function format_title_for_comparison( string $title ): string
	{
		$title = html_entity_decode( $title );
		$title = strtolower( $title );
		
		if( str_starts_with( $title, 'the ' ) ) {
			$title = substr( $title, 4 );
		} elseif( str_starts_with( $title, 'a ', ) ) {
			$title = substr( $title, 2 );
		}
		$title = trim( $title );
		$title = preg_replace( '/[^A-Za-z0-9]/', '', $title );

		return $title;
	}


	/**
	 * Load image/file dependencies
	 *
	 * @return 	true
	 */
	public static function load_image_file_system(): bool
	{
		if( !did_action( 'cine_loaded_image_file_system' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );

			WP_Filesystem();

			do_action( 'cine_loaded_image_file_system' );
		}

		return true;
	}
	

}
