<?php


namespace Cine\Models;


use WP_Post;
use DateTime;
use NumberFormatter;
use Vril_Utility;
use Exception;
use Cine\Core\Post_Types;
use Cine\Abstracts\Movie;
use Cine\Model\Production_Company;


defined( 'ABSPATH' ) || exit;


class Movie_Simple_Details extends Movie
{
	/**
	 * Create movie block from post ID or post
	 *
	 * @throws 	Exception 	Invalid post
	 * @param	int|WP_Post	$arg	Either post ID or WP_Post
	 */
	public function __construct( int|WP_Post $post ) 
	{
		parent::__construct( $post );

		$this
			->populate_genres()
			->populate_watch_status()
			->populate_rating()
			->populate_image_backdrop();
	}


	/**
	 * Extract specific movie properties
	 *
	 * @param	array
	 */
	public function package() 
	{
		return [
			'id' 		=> $this->id,
			'title' 	=> $this->title,
			'to_watch' 	=> $this->to_watch,
			'genres' 	=> $this->genres,
			'backdrop'	=> $this->image_backdrop_url,
			'rating' 	=> $this->rating,
		];
	}	

}
