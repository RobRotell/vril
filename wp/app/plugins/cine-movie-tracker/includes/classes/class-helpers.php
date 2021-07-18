<?php


namespace Movie_Tracker;


use Movie_Tracker;
use Error;


defined( 'ABSPATH' ) || exit;


class Helper
{
	/**
	 * Simple, recursive sanitizing function
	 *
	 * @param	mixed	$var 	Object/array/string sanitize
	 * @return	mixed 			Sanitized var
	 */
	public static function sanitize_var( $var ) 
	{
		if( is_object( $var ) || is_array( $var ) ) {
			foreach( $var as $key => &$value ) {
				$value = self::sanitize_var( $value );
			}
		} else {
			$var = trim( sanitize_text_field( $var ) );
		}

		return $var;
	}
	
	
	/**
	 * Simple integer-converting function
	 *
	 * @param	mixed	$var 	Variable to convert to integer
	 * @return	integer 		Integer
	 */	
	public static function convert_to_int( $var ): int 
	{
		if( is_array( $var ) && 1 === count( $var ) ) {
			return self::convert_to_var( $var );
		}

		return (int)$var;
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
		$term = get_terms(
			[
				'hide_empty'	=> false,
				'taxonomy'		=> Core::TAXONOMY,
				'name' 			=> $name,
				'number'		=> 1
			]
		);

		// if results, grab first result
		if( !empty( $term ) ) {
			$term = $term[0];

		// if no results, create term
		} else {
			$new_term 	= wp_insert_term( $name, Core::TAXONOMY );
			$term 		= get_term( $new_term['term_taxonomy_id'], Core::TAXONOMY );
		}

		return ( $id_only ) ? $term->term_id : $term;
	}


	/**
	 * Convert value to boolean
	 *
	 * @param	string 	$var 	Variable to convert to boolean
	 * @return 	boolean 		Boolean
	 */
	public static function convert_to_bool( $var ): bool
	{
		return in_array(
			$var,
			[ 1, '1', true, 'true' ]
		);
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
			Movie_Tracker::$plugin_path_inc, 
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
	

}
