<?php


namespace Cine\Controllers;


use stdClass;


defined( 'ABSPATH' ) || exit;


class TMDb
{
	const API_KEY_OPTION_NAME 	= 'cine_tmdb_apikey';
	const API_URL 				= 'https://api.themoviedb.org/3';
	const IMAGE_URL 			= 'https://image.tmdb.org/t/p';
	const POST_META_FIELD 		= 'id_tmdb';

	
	/**
	 * Get API key for TMDB
	 *
	 * @return 	string 	API key
	 */	
	public static function get_api_key(): string
	{
		return get_option( self::API_KEY_OPTION_NAME, '' );
	}

	
	/**
	 * Get API key for TMDB
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
	 * Basic fetch request to TMDB
	 *
	 * @param	string 	$api_slug 	API slug
	 * @param 	string 	$query 		Basic query arg
	 * @param 	int 	$page		Page (of results) to query
	 * 
	 * @return	object 				Response data 				
	 */
	private static function fetch( string $api_slug, string $query = null, int $page = null ): object 
	{
		$res_body	= new stdClass;
		$params 	= [];

		$params['api_key'] = self::get_api_key();

		// check for query param
		if( !empty( $query ) ) {
			$params['query'] = $query;
		}

		// check for page param (e.g. for multi-page results from TMDB)
		if( !empty( $page ) ) {
			$params['page'] = $page;
		}

		$api_url = sprintf( '%s/%s', self::API_URL, $api_slug );
		$api_url = add_query_arg( $params, $api_url );

		$res = wp_safe_remote_get( $api_url );

		// todo — exception on bad responses
		if( 200 === wp_remote_retrieve_response_code( $res ) ) {
			$res_body = json_decode( wp_remote_retrieve_body( $res ) );
		}

		return $res_body;
	}


	/**
	 * Wrapper for finding movies from TMDB by title
	 *
	 * @param	string 	$title 	Movie title
	 * @param 	int		$page 	Page of results
	 * 
	 * @return	array 			Contains results for page, total number of pages of results, total number of results
	 */
	public static function find_movie_by_title( string $title, int $page = 1 ): array
	{
		$slug = 'search/movie';
		
		$res = self::fetch( $slug, $title, $page );

		$page_results	= $res->results;
		$total_pages 	= $res->total_pages;
		$total_results 	= $res->total_results;

		return compact( 'page_results', 'total_pages', 'total_results' );
	}


	/**
	 * Wrapper for finding details for movie by TMDB ID
	 *
	 * @param	int		$id 	Movie ID
	 * @return	object 			Movie details 
	 */
	public static function fetch_movie_details( int $id ): object
	{
		$slug = sprintf( 'movie/%d', $id );

		return self::fetch( $slug );
	}


	/**
	 * Wrapper for finding credits for movie by TMDB ID
	 *
	 * @param	int		$id 	Movie ID
	 * @return	object 			Movie details 
	 */
	public static function fetch_movie_credits( int $id ): object
	{
		$slug = sprintf( 'movie/%d/credits', $id );

		return self::fetch( $slug );
	}	


	/**
	 * Build image URL based on size and image path
	 *
	 * @param	int		$width 	Width in pixels
	 * @param 	string 	$path 	Image path
	 * 
	 * @return 	string 			Image URL
	 */
	public static function build_image_url( int $width, string $path ): string
	{
		$width	= sprintf( 'w%s', $width );
		$path 	= ltrim( $path, '/' );

		$url = sprintf( '%s/%s/%s', self::IMAGE_URL, $width, $path );

		return esc_url_raw( $url );
	}


	/**
	 * Sanitize and specially transform data from TMDb
	 *
	 * @param	string 	$input	Input from TMDb
	 * @return 	string			Sanitized input
	 */
	public static function sanitize_convert_string( string $input = '' ): string
	{
		$sanitized = sanitize_text_field( $input );
		$converted = iconv( 'UTF-8', 'ASCII//TRANSLIT', $sanitized );

		return $converted;
	}

}
