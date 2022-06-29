<?php


namespace Cine\Api;


use \Tinify\Tinify as TinifyApi;
use \Tinify\Source as TinifySource;


defined( 'ABSPATH' ) || exit;


class Tinify
{
	private $set_key = false;


	/**
	 * Wrapper for prep
	 *
	 * @return 	void
	 */
	public function prep(): void
	{
		$this->include();
		$this->set_key();
	}


	/**
	 * Get dependencies
	 *
	 * @return 	void
	 */
	private function include(): void
	{
		require_once( Cine()::$plugin_path_inc . '/vendor/autoload.php' );
	}


	/**
	 * Get dependencies
	 *
	 * @return 	void
	 */
	private function set_key(): void
	{
		if( !$this->set_key ) {
			TinifyApi::setKey( Cine()->admin::get_tinify_apikey() );

			$this->set_key = true;
		}
	}


	/**
	 * Optimize image from raw image data
	 *
	 * @param 	string 	$data 	Imaga data
	 * @return 	string 			Optimized image data
	 */
	public function optimize_image_from_data( string $data ): string
	{
		$this->prep();

		return TinifySource::fromBuffer( $data )->toBuffer();
	}


	/**
	 * Optimize image from image path
	 *
	 * @param 	string 	$file_path 	Image file path
	 * @param 	string 	$new_path 	Path for optimized image. If empty, then original path will be overwritten
	 * 
	 * @return 	string 				File path for optimized image
	 */
	public function optimize_image_from_path( string $file_path, string $new_path = '' ): string
	{
		$this->prep();
		
		if( !is_file( $file_path ) ) {
			throw new Exception( sprintf( 'Invalid file: "%s"', $file_path ) );
		}

		$path_to_write = $new_path ?: $file_path;

		$source = TinifySource::fromFile( $file_path );
		$source->toFile( $path_to_write );

		return $path_to_write;
	}	
}
