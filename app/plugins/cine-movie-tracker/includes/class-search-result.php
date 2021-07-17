<?php

namespace Movie_Tracker;
use DateTime;

defined( 'ABSPATH' ) || exit;


class Search_Result
{
	public $id;
	public $title;
	public $year;
	public $description;
	public $poster;
	public $status;


	public function __construct( object $data )
	{
		if( !isset( $data->id ) || !isset( $data->title ) )
			return;

		$this->id 			= Helper::convert_to_int( $data->id );
		$this->title 		= Helper::sanitize_var( $data->title );
		$this->description 	= html_entity_decode( wp_trim_words( Helper::sanitize_var( $data->overview ), 20, ' [...]' ) );

		// some movies might not have been released yet
		$this->year = ( isset( $data->release_date ) ) ? self::get_release_year( $data->release_date ) : 'Not released yet';

		if( isset( $data->poster_path ) && !empty( $data->poster_path ) )
			$this->poster = esc_url_raw( sprintf( 'https://image.tmdb.org/t/p/w154/%s', ltrim( $data->poster_path, '/' ) ) );

		$this->check_if_already_added();
	}


	public function get_result()
	{
		return ( ( empty( $this->id ) || empty( $this->title ) || empty( $this->year ) ) ) ? false : $this;
	}


	private static function get_release_year( string $date )
	{
		$date = Helper::sanitize_var( $date );
		$date = DateTime::createFromFormat( 'Y-m-d', $date );

		if( !empty( $date ) )
			return $date->format( 'Y' );
	}


	/**
	 * Check if we've previously added this movie
	 *
	 * @return	void
	 */
	private function check_if_already_added()
	{
		$already_added = get_posts(
			[
				'post_type'		=> 'movie',
				'meta_key'		=> 'id_tmdb',
				'meta_value'	=> $this->id
			]
		);

		// already added? Find movie's watch status
		if( !empty( $already_added ) ) {
			foreach( $already_added as $movie ) {
				$status = get_field( 'to_watch', $movie->ID );
				if( $status === '1' || $status === 'true' || $status === true ) {
					$this->status = 'to_watch';
				} else {
					$this->status = 'watched';
				}

				break;
			}
		}
	}

}
