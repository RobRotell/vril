<?php

namespace Movie_Tracker;

defined( 'ABSPATH' ) || exit;


class Fetcher
{
	private static $api_base = 'https://api.themoviedb.org/3';

	
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
		$params = [
			'api_key' => self::get_api_key()
		];

		// check for query param
		if( !empty( $query ) )
			$params['query'] = $query;

		// check for page param (e.g. for multi-page results from TMDB)
		if( !empty( $page ) )
			$params['page'] = $page;

		// build URL to fetch
		$url = sprintf( 
			'%s/%s?%s', 
			self::$api_base, 
			$api_slug,
			http_build_query( $params )
		);

		$request = wp_safe_remote_get( $url );
		$response = wp_remote_retrieve_response_code( $request );

		// exit early if error occurred
		if( $response !== 200 )
			return false;

		return json_decode( wp_remote_retrieve_body( $request ) );
	}


	/**
	 * Wrapper for finding movies from TMDB by title
	 *
	 * @param	string 	$title 	Movie title
	 * @param 	integer $limit 	Limit of results
	 * @return	array 			Matches
	 */
	public static function find_movie_by_title( string $title, int $limit = 10 )
	{
		$page = 1;
		$slug = 'search/movie';
		
		// get first page of results
		$request = self::fetch( $slug, $title, $page );
		$results = $request->results;

		// grab more results?
		if( count( $results ) < $limit ) {

			// check for additional pages
			if( property_exists( $request, 'total_pages' ) && $request->total_pages !== 1 ) {
				while( ++$page <= $request->total_pages && count( $results ) < $limit ) {
					$page_results = self::fetch( $slug, $title, $page );
					if( !empty( $page_results ) )
						$results = array_merge( $results, $page_results->results );
				}
			}
		}

		return array_slice( $results, 0, $limit );
	}


	/**
	 * Wrapper for finding details for movie by TMDB ID
	 *
	 * @param	integer	$id 	Movie ID
	 * @return	object 			Movie details 
	 */
	public static function find_movie_details( int $id )
	{
		$slug = sprintf( 'movie/%d', $id );
		return self::fetch( $slug );
	}


	/**
	 * Wrapper for finding credits for movie by TMDB ID
	 *
	 * @param	integer	$id 	Movie ID
	 * @return	object 			Movie details 
	 */
	public static function find_movie_credits( int $id )
	{
		$slug = sprintf( 'movie/%d/credits', $id );
		return self::fetch( $slug );
	}	


	/**
	 * Get TMDB API key, stored in WP
	 *
	 * @return	string 	API key
	 */
	private static function get_api_key()
	{
		return get_option( Admin::$option_apikey );
	}	


}
