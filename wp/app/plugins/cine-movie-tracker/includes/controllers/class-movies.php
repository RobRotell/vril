<?php


namespace Cine\Controllers;


use Cine\Core\Post_Types;
use WP_Post;
use Cine\Controllers\TMDb;
use Cine\Models\Movie_To_Add;


defined( 'ABSPATH' ) || exit;


class Movies
{
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


	/**
	 * Get ID of movie post based on TMDb ID
	 *
	 * @param	int		$id		TMDb ID
	 * @return 	int|false		Post ID if match; otherwise, false
	 */
	public static function get_movie_post_id_by_tmdb_id( int $id ): int|false
	{
		$args = [
			'fields'		=> 'ids',
			'meta_key'		=> TMDb::POST_META_FIELD,
			'meta_value'	=> $id,
			'post_type'		=> Post_Types::POST_TYPE_KEY,
		];

		$posts = get_posts( $args );

		return $posts ? array_unshift( $posts ) : false;
	}


	/**
	 * Get TMDb ID for movie post
	 *
	 * @param	int		$id		Post ID
	 * @return 	int|false		TMDb if valid movie post; otherwise, false
	 */
	public static function get_tmdb_id_by_movie_post_id( int $id ): int|false
	{
		$post = get_post( $id );

		if( !$post || !self::assert_post_is_movie( $id ) ) {
			return false;
		}

		return get_field( TMDb::POST_META_FIELD, $id );
	}	


	/**
	 * Create movie post from TMDb ID
	 * 
	 * @throws	Exception 	No movie matches TMDb ID; failed to create post
	 *
	 * @param	int		$id		TMDb ID
	 * @return 	int				Post ID for new movie
	 */
	public static function create_movie_from_tmdb_id( int $id ): int
	{
		// double check that movie hasn't already been saved
		if( !empty( $post_id = self::get_movie_post_id_by_tmdb_id( $id ) ) ) {
			return $post_id;
		}

		$movie = new Movie_To_Add( $id );
		$movie
			->process_data_from_tmdb();

		die;
		// 	->save();

		// return $movie->get_post_id();
	}

}
