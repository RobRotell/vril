<?php


namespace Cine\Core;


use Cine\Core\Post_Types;
use Cine\Core\Taxonomies;
use Cine\Core\Taxonomy_Genres;
use Cine\Core\Taxonomy_Production_Companies;


defined( 'ABSPATH' ) || exit;


class Transients
{
	const PREFIX 		= 'cine_v2_';
	const EXPIRATION 	= WEEK_IN_SECONDS;


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
		add_action( 
			'save_post_'. Post_Types::POST_TYPE_KEY,
			[ $this, 'delete_transients' ] 
		);
		
		add_filter( 
			'acf/update_value',
			[ $this, 'handle_acf_update' ], 
			10, 4 
		);

		foreach( [ Taxonomy_Genres::TAXONOMY_KEY, Taxonomy_Production_Companies::TAXONOMY_KEY ] as $taxonomy ) {
			add_action( 
				'edited_' . $taxonomy,
				[ $this, 'delete_transients' ] 
			);
			
			add_action( 
				'create_' . $taxonomy,
				[ $this, 'delete_transients' ] 
			);
		}
	}


	/**
	 * Convert arg to key
	 *
	 * @param 	string 	$name 	Name
	 * @param	mixed	$extra  Extra data to use in generating transient key
	 * 
	 * @return 	string 			Key
	 */
	public static function create_transient_key( string $action = '', $arg = null ): string
	{
		$key = self::PREFIX . $action;

		if( null !== $arg ) {
			$arg = maybe_serialize( $arg );
			$key = sprintf( '%s__%s', $key, md5( $arg ) );
		}

		return $key;
	}


	/**
	 * Wrapper for getting transient data
	 *
	 * @param 	string 	$name 	Name
	 * @param	mixed	$extra  Extra data to use in generating transient key
	 * 
	 * @return 	mixed|false		False, if transient doesn't exist; otherwise, data
	 */
	public static function get_transient( string $name, $data = null )
	{
		$key = self::create_transient_key( $name, $data );
		
		return get_transient( $key );
	}


	/**
	 * Wrapper for setting transient
	 * 
	 * @param 	string 	$name 	Name
	 * @param	mixed	$extra  Extra data to use in generating transient key
	 * @param 	mixed 	$data 	Data to save to transient

	 * @return 	bool 			Always true, regardless of whether transient was actually set
	 */
	public static function set_transient( string $name, $arg = null, $data = false ): bool
	{
		if( false !== $data ) {
			$key = self::create_transient_key( $name, $arg );

			set_transient( $key, $data, self::EXPIRATION );
		}

		return true;
	}


	/**
	 * Deletes all related transients
	 * 
	 * @todo 	Break it out into actions
	 *
	 * @return 	bool 	Always true
	 */
	public static function delete_transients(): bool
	{
		global $wpdb;

		// first, let's get all plugin-specific transient names
		$prefix = $wpdb->esc_like( sprintf( '_transient_%s', self::PREFIX ) );
		$sql 	= "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s'";
		$keys   = $wpdb->get_results( $wpdb->prepare( $sql, $prefix . '%' ), ARRAY_A );

		foreach( $keys as $key ) {
			$option_name = $key['option_name'];
			$option_name = ltrim( $option_name, '_transient_' );

			delete_transient( $option_name );
		}

		return true;
	}


	/**
	 * Check if ACF update was for specific post type
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

			if( !empty( $post ) && $post->post_type === Post_Types::POST_TYPE_KEY ) {
				self::delete_transients();
			}
		}		

		return $value;
	}

}
