<?php


namespace Cine\Controllers;


use Cine\Core\Post_Types;
use Cine\Core\Taxonomies;


defined( 'ABSPATH' ) || exit;


class Last_Updated
{
	const OPTION_UPDATE = 'cine_last_updated';


	/**
	 * Creates class
	 *
	 * @return 	void
	 */	
	public function __construct()
	{
		$this->add_wp_hooks();
	}


	/**
	 * Hooks into WordPress
	 *
	 * @return 	void
	 */	
	private function add_wp_hooks()
	{
		$post_type	= Post_Types::POST_TYPE;
		$taxonomy 	= Taxonomies::TAXONOMY;	
		
		add_action( 'save_post_'. $post_type,	[ $this, 'update_timestamp' ] );
		add_filter( 'acf/update_value',			[ $this, 'handle_acf_update' ], 10, 4 );

		add_action( 'edited_' . $taxonomy, 		[ $this, 'update_timestamp' ] );
		add_action( 'create_' . $taxonomy, 		[ $this, 'update_timestamp' ] );		
	}


	/**
	 * Update timestamp for when movies were last updated
	 *
	 * @return 	void
	 */
	public static function update_timestamp()
	{
		update_option( self::OPTION_UPDATE, time() );
	}


	/**
	 * Get timestamp for when movies and genres were last updated
	 *
	 * @return 	string 	Timestamp
	 */
	public static function get_timestamp()
	{
		return get_option( self::OPTION_UPDATE, '' );
	}


	/**
	 * Check if movie was updated, and then update last updated value
	 *
	 * @param	mixed 	$value 		New field value
	 * @param 	mixed 	$post_id 	ID of post being saved/updated
	 * @param 	array 	$field 		Field data
	 * @param 	mixed 	$orig_value Original field value
	 * 
	 * @return 	mixed 				New field value (no change will take place)
	 */
	public static function handle_acf_update( $value, $post_id, $field, $orig_value )
	{
		if( $value !== $orig_value ) {
			$post = get_post( $post_id );

			if( !empty( $post ) && $post->post_type === Post_Types::POST_TYPE ) {
				self::update_timestamp();
			}
		}

		return $value;
	}	

}
