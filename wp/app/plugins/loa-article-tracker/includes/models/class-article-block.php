<?php


namespace Loa\Model;

use Vril_Utility;
use WP_Post;


defined( 'ABSPATH' ) || exit;


class Article_Block
{
    public $id             = null;
    public $title          = '';

    public $url            = '';

    public $is_read        = false;
    public $is_favorite    = false;
    
    public $tags           = [];


    public function __construct( WP_Post $post )
    {
        $this->id = $post->ID;
        $this->title = htmlspecialchars_decode( $post->post_title, ENT_QUOTES | ENT_HTML5 );

        $this
            ->get_meta_data()
            ->get_tags();
    }


    private function get_meta_data()
    {
        $this->is_read      = Vril_Utility::convert_to_bool( get_field( 'article_read', $this->id ) );
        $this->is_favorite  = Vril_Utility::convert_to_bool( get_field( 'article_favorite', $this->id ) );
        
        $this->url = esc_url( get_field( 'article_url', $this->id ) );

        // if no actual title, use URL for title
        if( empty( $this->title ) ) {
            $this->title = $this->url;
        }        

        return $this;
    }


    private function get_tags()
    {
        $tags = wp_get_object_terms( 
            $this->id, 
            Loa()->core::TAXONOMY, 
            [ 
                'fields' => 'id=>name' 
            ] 
        );

        foreach( $tags as $term_id => $term_name ) {
            $this->tags[] = [
                'id'    => absint( $term_id ),
                'name'  => $term_name
            ];
        }

        return $this;
    }


    public function package()
    {
        return get_object_vars( $this );
    }

}
