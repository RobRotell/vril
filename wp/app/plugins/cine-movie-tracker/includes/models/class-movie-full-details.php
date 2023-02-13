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


class Movie_Full_Details extends Movie
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
			->populate_synopsis()
			->populate_genres()
			->populate_watch_status()
			->populate_rating()
			->populate_image_backdrop()
			->populate_image_poster()
			->populate_credits()
			->populate_dates()
			->populate_finances();
	}


	/**
	 * Extract specific movie properties
	 *
	 * @param	array
	 */
	public function package() 
	{
		return [
			'id' 			=> $this->id,
			'title' 		=> $this->title,
			'synopsis' 		=> $this->synopsis,
			'release_date'	=> $this->release_date,
			'release_year' 	=> $this->release_year,
			'to_watch' 		=> $this->to_watch,
			'directors' 	=> $this->directors,
			'writers' 		=> $this->writers,
			'budget' 		=> $this->budget,
			'box_office' 	=> $this->box_office,
			'website' 		=> $this->website,
			'genres' 		=> $this->genres,
			'backdrop' 		=> $this->image_backdrop_url,
			'poster' 		=> $this->image_poster_url,
			'rating' 		=> $this->rating,
			'runtime' 		=> $this->runtime,
		];
	}	

}
