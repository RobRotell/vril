<?php


namespace Loa;


use WP_Post;


defined( 'ABSPATH' ) || exit;


class Article
{
    private $id             = null;

    private $url            = '';
    private $title          = '';

    private $date_added     = null;
    private $date_read      = null;
    private $is_favorite    = false;
    
    private $tags = [];


    public function __construct( $arg )
    {
        if( is_numeric( $arg ) ) {
            $arg = absint( $arg );
            $arg = get_post( $arg );
        }

        if( !is_a( $arg, 'WP_Post' ) ) {
            return null;
        }

        if( Core::POSTTYPE !== $arg->post_type ) {
            return;
        }
        
        $this->id = $arg->ID;
        $this->title = $arg->post_title;

        $this->set_atts();

        return $this;
    }


    private function set_atts()
    {
        $this->url          = esc_url_raw( get_field( 'article_url', $this->id ) );
        $this->is_read      = get_field( 'article_is_read', $this->id );
        $this->is_favorite  = get_field( 'article_is_favorite', $this->id );

        if( 'Article' === $this->title ) {
            $this->title = $this->url;
        }

        $this->title = html_entity_decode( 
            preg_replace( '/U\+([0-9A-F]{4})/', '&#x\\1;', $this->title ), 
            ENT_NOQUOTES, 
            'UTF-8' 
        );

        if( !empty( $date_added = get_field( 'article_date_added', $this->id ) ) ) {
            $this->date_added = $this->format_date( $date_added );
        }

        if( !empty( $date_read = get_field( 'article_date_read', $this->id ) ) ) {
            $this->date_read = $this->format_date( $date_read );
        }

        $this->tags = wp_get_object_terms( 
            $this->id, 
            Core::TAXONOMY,
            [
                'fields' => 'id=>name'
            ]
        );
    }


    public function is_unread()
    {
        return empty( $this->date_read );
    }


    public function get_atts()
    {
        return get_object_vars( $this );
    }


    public function format_date( $date ) 
    {
        if( !empty( $date ) ) {
            $date = strtotime( $date );
            $date = date( 'Y-m-d', $date );
        }

        return $date;
    }


}