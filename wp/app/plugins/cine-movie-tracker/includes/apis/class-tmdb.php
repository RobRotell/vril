<?php


namespace Cine\Api;


defined( 'ABSPATH' ) || exit;


class TMDB
{
	const API_URL = 'https://api.themoviedb.org/3';

	private const IMAGE_URL = 'https://image.tmdb.org/t/p';


	
	/**
	 * Basic fetch request to TMDB
	 *
	 * @param	string 	$api_slug 	API slug
	 * @param 	string 	$query 		Basic query arg
	 * @param 	int 	$page		Page (of results) to query
	 * 
	 * @return	object 				Response data 				
	 */
	private static function fetch( string $api_slug, string $query = null, int $page = null ) 
	{
		$results	= [];
		$params 	= [];

		$params['api_key'] = Cine()->admin::get_tmdb_apikey();

		// check for query param
		if( !empty( $query ) ) {
			$params['query'] = $query;
		}

		// check for page param (e.g. for multi-page results from TMDB)
		if( !empty( $page ) ) {
			$params['page'] = $page;
		}

		// build URL to fetch
		$url = sprintf( 
			'%s/%s?%s', 
			self::API_URL, 
			$api_slug,
			http_build_query( $params )
		);

		$request = wp_safe_remote_get( $url );

		if( 200 === wp_remote_retrieve_response_code( $request ) ) {
			$results = json_decode( wp_remote_retrieve_body( $request ) );
		}

		return $results;
	}


	/**
	 * Wrapper for finding movies from TMDB by title
	 *
	 * @param	string 	$title 	Movie title
	 * @param 	int		$limit 	Limit of results
	 * @return	array 			Matches
	 */
	public static function find_movie_by_title( string $title, int $limit = 10 )
	{
		$page = 1;
		$slug = 'search/movie';
		
		$request = self::fetch( $slug, $title, 1 );
		if( empty( $request ) || !property_exists( $request, 'results' ) ) {
			return [];

		} else {
			$results = $request->results;

			// grab more results?
			if( $limit > count( $results ) ) {
				if( property_exists( $request, 'total_pages' ) && 1 !== $request->total_pages ) {
					while( ++$page <= $request->total_pages && count( $results ) < $limit ) {
						$page_results = self::fetch( $slug, $title, $page );
						if( !empty( $page_results ) && property_exists( $page_results, 'results' ) ) {
							$results = array_merge( $results, $page_results->results );
						}
					}
				}
			}

			return array_slice( $results, 0, $limit );
		}
	}


	/**
	 * Wrapper for finding details for movie by TMDB ID
	 *
	 * @param	int		$id 	Movie ID
	 * @return	object 			Movie details 
	 */
	public static function find_movie_details( int $id ): object
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
	public static function find_movie_credits( int $id ): object
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

}
