<?php


namespace Cine\Model;


use WP_Post;
use DateTime;
use NumberFormatter;
use Vril_Utility;


defined( 'ABSPATH' ) || exit;


class Movie_Block
{
	public $id;
	public $title;
	public $content;
	
	public $year;
	public $to_watch;

	public $website;
	public $director;
	public $writer;
	public $budget;
	public $box_office;
	
	public $backdrop;
	public $poster;


	public function __construct( WP_Post $post ) {
		$this->id 		= $post->ID;
		$this->title 	= html_entity_decode( $post->post_title );
		
		$this->grab_simple_details();		
	}


	public function grab_simple_details() 
	{
		$watch_status = get_field( 'to_watch', $this->id );
		$this->to_watch = Vril_Utility::convert_to_bool( $watch_status );

		$this->director = get_field( 'director', $this->id );	

		$release_date = get_field( 'release_date', $this->id );
		$release_date = new DateTime( $release_date );
		$this->year = $release_date->format( 'Y' );		

		// for image, first check for backdrop. If none, try poster
		$image_id = get_field( 'backdrop', $this->id );
		if( empty( $image_id ) ) {
			$image_id = get_post_thumbnail_id( $this->id );
		}
		
		if( !empty( $image_id ) ) {
			$this->backdrop = wp_get_attachment_image_url( $image_id, 'backdrop_small' );
		}

		return $this;
	}


	public function grab_all_details() 
	{
		$content = get_the_content( null, true, $this->id );
		$this->content = html_entity_decode( $content );

		$this->writer 		= get_field( 'writer', $this->id );

		$this->budget 		= $this->convert_to_dollar( get_field( 'budget', $this->id ) );
		$this->box_office 	= $this->convert_to_dollar( get_field( 'box_office', $this->id ) );
		$this->website 		= get_field( 'website', $this->id );

		$poster_id 			= get_post_thumbnail_id( $this->id );
		$this->poster 		= wp_get_attachment_image_url( $poster_id, 'large' );	

		return $this;
	}


	public function convert_to_dollar( $value ) 
	{
		if( !empty( $value = (int)$value ) && class_exists( 'NumberFormatter' ) ) {
			$format = new NumberFormatter( 'en_US', NumberFormatter::DECIMAL );
			$value = sprintf( '$%s', $format->format( $value ) );
		}

		return $value;
	}


	public function package()
	{
		return get_object_vars( $this );
	}

}
