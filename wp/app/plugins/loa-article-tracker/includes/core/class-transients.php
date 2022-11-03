<?php


namespace Loa\Core;


defined( 'ABSPATH' ) || exit;


class Transients
{
	const PREFIX 		= 'loa_v3_';
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
		$post_type	= Loa()->post_types::POST_TYPE;
		$taxonomy 	= Loa()->post_types::TAXONOMY;	
		
		add_action( 'save_post_'. $post_type,	[ $this, 'delete_transients' ] );
		add_filter( 'acf/update_value',			[ $this, 'handle_acf_update' ], 10, 2 );

		add_action( 'edited_' . $taxonomy, 		[ $this, 'delete_transients' ] );
		add_action( 'create_' . $taxonomy, 		[ $this, 'delete_transients' ] );		
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
	 * Convert arg to key
	 *
	 * @param 	string 	$name 	Name
	 * @param	mixed	$extra  Extra data to use in generating transient key
	 * 
	 * @return 	string 			Key
	 */
	private static function create_transient_key( string $action = '', $arg = null ): string
	{
		$key = self::PREFIX . $action;

		if( null !== $arg ) {
			$arg = maybe_serialize( $arg );
			$key = sprintf( '%s__%s', $key, md5( $arg ) );
		}

		return $key;
	}


	/**
	 * Wrapper for setting transient
	 * 
	 * @param 	string 	$name 	Name
	 * @param	mixed	$extra  Extra data to use in generating transient key
	 * @param 	mixed 	$data 	Data to save to transient

	 * @return 	bool 			Always true, regardless of whether transient was actually set
	 */
	public static function set_transient( string $action, $arg = null, $data = null ): bool
	{
		if( null !== $data ) {
			$key = self::create_transient_key( $action, $arg );

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
	public function delete_transients(): bool
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
	 * 
	 * @return 	mixed 				Original field value
	 */
	public function handle_acf_update( $value, $post_id )
	{
		$post = get_post( $post_id );

		if( $post && $post->post_type === Loa()->post_types::POST_TYPE ) {
			$this->delete_transients();
		}

		return $value;
	}

}
