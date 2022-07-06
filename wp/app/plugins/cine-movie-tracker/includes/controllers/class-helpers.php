<?php


namespace Cine\Controllers;

use Cine\Core\Post_Types;
use Cine\Core\Taxonomy_Genres;
use WP_Post;


defined( 'ABSPATH' ) || exit;


class Helpers
{
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
		$taxonomy = Taxonomy_Genres::TAXONOMY_KEY;

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
		if( !did_action( 'cine/helpers/load_image_filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );

			WP_Filesystem();

			do_action( 'cine/helpers/load_image_filesystem' );
		}

		return true;
	}


	/**
	 * Quick check to confirm provided post or post ID is a movie
	 *
	 * @param	int|WP_Post	$post	Post ID or WP_Post
	 * @return 	bool 				True, if movie
	 */
	public static function assert_post_is_movie( int|WP_Post $post ): bool
	{
		if( is_int( $post ) ) {
			$post = get_post( $post );
		}

		return $post && $post->post_type === Post_Types::POST_TYPE_KEY;
	}

}
