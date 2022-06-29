<?php


namespace Cine\Controllers;


use \Tinify\Tinify as TinifyApi;
use \Tinify\Source as TinifySource;


defined( 'ABSPATH' ) || exit;


class Tinify
{
	const API_KEY_OPTION_NAME = 'cine_tinify_apikey';

	
	/**
	 * Get API key for Tinify
	 *
	 * @return 	string 	API key
	 */	
	public static function get_api_key(): string
	{
		return get_option( self::API_KEY_OPTION_NAME, '' );
	}

	
	/**
	 * Get API key for Tinify
	 *
	 * @param	string	$api_key	New API key 	
	 * @return 	bool				True, if new API key was saved
	 */	
	public static function set_api_key( string $api_key ): bool
	{
		if( !current_user_can( 'manage_options' ) ) {
			return false;
		}

		return update_option( self::API_KEY_OPTION_NAME, $api_key );
	}	


	/**
	 * Optimize image from raw image data
	 *
	 * @param 	string 	$data 	Imaga data
	 * @return 	string 			Optimized image data
	 */
	public function optimize_image_from_data( string $data ): string
	{
		require_once( Cine()::$plugin_path . '/vendor/autoload.php' );
		TinifyApi::setKey( self::get_api_key() );

		return TinifySource::fromBuffer( $data )->toBuffer();
	}


	/**
	 * Optimize image from file
	 * 
	 * @throws 	Exception 			Invalid file path
	 *
	 * @param 	string 	$file_path 	Image file path
	 * @param 	string 	$new_path 	Path for optimized image. If empty, then original path will be overwritten
	 * 
	 * @return 	string 				File path for optimized image
	 */
	public function optimize_image_from_path( string $file_path, string $new_path = '' ): string
	{
		require_once( Cine()::$plugin_path . '/vendor/autoload.php' );
		TinifyApi::setKey( self::get_api_key() );
		
		if( !is_file( $file_path ) ) {
			throw new Exception( sprintf( 'Invalid file: "%s"', $file_path ) );
		}

		$path_to_write = $new_path ?: $file_path;

		$source = TinifySource::fromFile( $file_path );
		$source->toFile( $path_to_write );

		return $path_to_write;
	}	
}
