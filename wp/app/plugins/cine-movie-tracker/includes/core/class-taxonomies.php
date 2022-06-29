<?php


namespace Cine\Core;


defined( 'ABSPATH' ) || exit;


class Taxonomies
{
    const TAXONOMY          = 'genre';
    const TAXONOMY_PLURAL   = 'genres';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
        add_action( 'init', [ $this, 'add_taxonomy' ] );		
	}


    public function add_taxonomy()
    {
        register_taxonomy( 
            self::TAXONOMY, 
            Post_Types::POST_TYPE,
            [
                'label'             => self::TAXONOMY_PLURAL,
                'show_tagcloud'     => false,
                'show_admin_column' => true,
                'labels'            => [
                    'name'          => ucwords( self::TAXONOMY_PLURAL ),
                    'singular_name' => ucwords( self::TAXONOMY ),
                ],
            ]
        );
    }
    
}
