<?php


namespace Cine\Abstracts;


use DateTime;


defined( 'ABSPATH' ) || exit;


abstract class Movie
{
	public ?int 	$id_tmdb;
	public ?string 	$title;
	public ?string 	$synopsis;
	public ?string 	$tagline;
	
	public ?DateTime $release_datetime;
	public ?string	$release_date; // YYYY-mm-dd
	public ?int		$release_year;

	public ?bool 	$to_watch;
	public ?bool 	$watched;

	public ?string 	$website;
	public ?string 	$runtime;

	public ?array 	$directors;
	public ?array 	$writers;

	public ?string 	$budget;
	public ?string 	$box_office;

	public ?array 	$genres;
	public ?array 	$production_companies;

	public ?int 	$image_id_backdrop;
	public ?int 	$image_id_poster;


	/**
	 * Convert value to USD
	 *
	 * @param	int	$value		Original USD amount
	 * @return	string|false	String, if could be converted to original USD amount; otherwise false
	 */
	public function convert_to_dollar( int $value ): string|false 
	{
		if( !empty( $value ) && class_exists( 'NumberFormatter' ) ) {
			$format = new NumberFormatter( 'en_US', NumberFormatter::DECIMAL );

			return sprintf( '$%s', $format->format( $value ) );
		}

		return false;
	}

}
