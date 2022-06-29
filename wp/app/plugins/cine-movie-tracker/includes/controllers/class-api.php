<?php


namespace Cine\Controller;


use Exception;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_Query;


use Cine\Model\Api_Response as Api_Response;
use Cine\Model\New_Movie as New_Movie;
use Cine\Model\Search_Result as Search_Result;
use Cine\Model\Movie_Block as Movie_Block;


defined( 'ABSPATH' ) || exit;


class API
{
	/**
	 * Create standardized response object
	 *
	 * @param	string			$keys 	Data keys
	 * @return	Api_Response 			Custom API response obj
	 */
	private static function create_response_obj( string ...$keys ): Api_Response
	{
		$response = new Api_Response();

		foreach( $keys as $key ) {
			$response->add_data_key( $key );
		}

		return $response;
	}


	/**
	 * Get movies from database based on request parameters
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */
	public function get_movies( WP_Rest_Request $request ): WP_REST_Response
	{
		$page 		= $request->get_param( 'page' );
		$count 		= $request->get_param( 'count' );
		$genre 		= $request->get_param( 'genre' );
		$keyword 	= $request->get_param( 'keyword' );
		$to_watch 	= $request->get_param( 'to_watch' );
		$no_cache 	= $request->get_param( 'no_cache' );

		// prep response object
		$res = self::create_response_obj( 'meta', 'movies' );

		try {
			$meta 	= [];
			$movies = [];

			$fetch_new 		= false;
			$last_updated 	= Cine()->admin::get_last_updated();
	
			if( $no_cache ) {
				$fetch_new = true;
			} else {
				$transient_key = compact( 'page', 'count', 'genre', 'keyword', 'to_watch' );
				$transient_key = http_build_query( $transient_key );
				$transient_key = sprintf( 'cine_fetch_%s', md5( $transient_key ) );
		
				$cached_data = get_transient( $transient_key );
				if( !isset( $cached_data['meta']['last_updated'] ) || $last_updated !== $cached_data['meta']['last_updated'] ) {
					$fetch_new = true;
				} elseif( !isset( $cached_data['movies'] ) || empty( $cached_data['movies'] ) ) {
					$fetch_new = true;
				} else {
					$meta 	= $cached_data['meta'];
					$movies = $cached_data['movies'];
				}
			}
	
			if( $fetch_new ) {
				$query_args = [
					'post_type' 		=> Cine()->core::POST_TYPE,
					'posts_per_page'	=> $count,
					'paged'				=> $page,
					'order'				=> 'ASC',
					'orderby'			=> 'meta_value',
					'meta_key'			=> 'title_for_compare',
				];
	
				if( !empty( $keyword ) ) {
					$query_args['s'] = $keyword;
				}
	
				if( !empty( $genre ) ) {
					$query_args['tax_query'] = [
						[
							'taxonomy'	=> Cine()->core::TAXONOMY,
							'terms'		=> $genre
						]
					];
				}
	
				if( $to_watch ) {
					$query_args['meta_query'] = [
						[
							'key'	=> 'to_watch',
							'value'	=> true
						]
					];
				}

				$query = new WP_Query( $query_args );

				// additional metadata for frontend
				$total_count = absint( $query->found_posts );
				$total_pages = ceil( $total_count / $count );	
				
				$meta = compact( 'last_updated', 'page', 'total_pages', 'total_count' );				
	
				foreach( $query->posts as $post ) {
					$movie 		= new Movie_Block( $post );
					$movies[] 	= $movie->package();
	
					unset( $movie );
				}
	
				if( !$no_cache ) {
					set_transient( $transient_key, compact( 'meta', 'movies' ) );
				}
			}

			$res
				->add_data( $meta, 'meta' )
				->add_data( $movies, 'movies' );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );
	}


	/**
	 * Get last updated time
	 * 
	 * Apps can use this option as a way to quickly check if the values saved in cache/local storage are out-of-date. 
	 * A greater last updated time denotes that content has changed since last request.
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */
	public function get_last_updated_time( WP_Rest_Request $request ): WP_REST_Response
	{
		// prep response object
		$res = self::create_response_obj();

		$res->add_data( Admin::get_last_updated(), 'last_updated' );

		return rest_ensure_response( $res->package() );
	}	


	/**
	 * Get movie by post ID
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */
	public function get_movie_by_id( WP_Rest_Request $request ): WP_REST_Response
	{
		$id = $request->get_param( 'id' );
				
		// prep response object
		$res = self::create_response_obj( 'movie' );

		try {

			$transient_key = compact( 'id' );
			$transient_key = http_build_query( $transient_key );
			$transient_key = sprintf( 'cine_fetch_movie_by_id_%s', md5( $transient_key ) );

			$movie = get_transient( $transient_key );

			if( empty( $movie ) ) {

				// object for displaying movie details

				$post = get_post( $id );
				if( empty( $post ) ) {
					$res->set_error( 
						sprintf( 'No movie matched ID: "%s"', $id ),
						404
					);
				} else {
					$movie = new Movie_Block( $post );
					$movie
						->grab_all_details()
						->package();

						
					set_transient( $transient_key, $movie );
				}
			}
				
			$res->add_data( $movie, 'movie' );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );
	}


	/**
	 * Search TMDB for movie based on title keyword
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */
	public function search_by_title( WP_Rest_Request $request ): WP_REST_Response
	{
		$title = $request->get_param( 'title' );
		$limit = $request->get_param( 'limit' );

		// prep response object
		$res = self::create_response_obj( 'results' );
		
		try {
			// query TheMovieDatabase for movies that match title
			$results = Cine()->tmdb::find_movie_by_title( $title, $limit );

			if( empty( $results ) ) {
				$res->set_error(
					sprintf( 'No results found for "%s"', $title ),
					404
				);
			} else {
				$i = 0;
				foreach( $results as $result ) {
					if( $i > $limit ) {
						break;
					}
					
					// convert to pretty search results
					$result = new Search_Result( $result );

					$res->add_data( $result->package(), 'results' );

					++$i;
				}
			}

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );
	}


	/**
	 * Add movie to WP DB based on TMDB movie ID
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */	
	public function add_movie_by_id( WP_Rest_Request $request ): WP_REST_Response
	{
		$tmdb_id	= $request->get_param( 'id' );
		$status 	= $request->get_param( 'to_watch' );

		// prep response object
		$res = self::create_response_obj( 'movie' );

		try {
			// query TMDB for movie details
			$details = Cine()->tmdb::find_movie_details( $tmdb_id );

			// query TMDB for movie credits
			$credits = Cine()->tmdb::find_movie_credits( $tmdb_id );

			// setup basic movie
			$new_movie = new New_Movie( $details );
			$new_movie
				->set_watch_status( $status )
				->set_credits( $credits );

			// save as movie post
			$post_id = $new_movie->save_as_post();

			$movie_post = new Movie_Block( get_post( $post_id ) );
			$movie_post
				->grab_all_details()
				->package();
			
			$res->add_data( $movie_post, 'movie' );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );
	}


	/**
	 * Set "to watch" movie as "watched"
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */	
	public function set_movie_as_watched( WP_Rest_Request $request ): WP_REST_Response
	{
		$movie_id = $request->get_param( 'id' );

		// prep response object
		$res = self::create_response_obj( 'movie' );

		try {
			$post = get_post( $movie_id );
			if( empty( $post ) || $post->post_type !== Cine()->core::POST_TYPE ) {
				throw new Exception( 
					sprintf(
						'No movie matches ID: "%s"', 
						$movie_id 
					)
				);
			}

			$success = update_field( 'to_watch', false, $movie_id );

			/**
			 * ACF returns false if trying to update a field to a value that was actually already set. Instead of 
			 * returning an error, let's double-check the new value. 
			 * 
			 */ 
			if( !$success ) {

				// should be false if saved correctly
				if( get_field( 'to_watch', $movie_id ) ) {
					throw new Exception( 
						sprintf( 
							'Failed to update watch status for "%s" (ID: "%s")', 
							$post->post_title, 
							$movie_id 
						) 
					);
				}
			}

			$movie = new Movie_Block( $post );
			$movie
				->grab_all_details()
				->package();

			$res->add_data( $movie, 'movie' );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );		
	}	


	/**
	 * Delete movie from WP DB
	 *
	 * @param 	WP_Rest_Request 	$request 	API request
	 * @return	WP_REST_Response 				REST API response
	 */	
	public function delete_movie( WP_Rest_Request $request ): WP_REST_Response
	{
		$movie_id = $request->get_param( 'id' );

		// prep response object
		$res = self::create_response_obj();

		try {
			$post = get_post( $movie_id );
			if( empty( $post ) || $post->post_type !== Cine()->core::POST_TYPE ) {
				throw new Exception( 
					sprintf( 
						'No movie matches ID: "%s"', 
						$movie_id 
					) 
				);
			}

			// delete movie poster image
			if( !empty( $thumb_id = get_post_thumbnail_id( $movie_id ) ) ) {
				wp_delete_attachment( $thumb_id, true );
			}

			// delete movie backdrop imageimage for backdrop
			if( !empty( $attachment_id = get_field( 'backdrop', $movie_id ) ) ) {
				wp_delete_attachment( $attachment_id, true );
			}

			$deleted_post = wp_delete_post( $movie_id, true );
			if( empty( $deleted_post ) ) {
				throw new Exception( 
					sprintf( 
						'Failed to delete movie: "%s" with ID: "%s"',
						$post->post_title,
						$movie_id
					)
				);
			}

			$res->add_data( true, 'deleted' );

		} catch( Throwable $e ) {
			$res->set_error( $e->getMessage() );
		}

		return rest_ensure_response( $res->package() );
	}	

}
