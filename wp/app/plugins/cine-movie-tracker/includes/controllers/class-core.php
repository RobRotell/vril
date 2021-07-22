<?php


namespace Cine;


defined( 'ABSPATH' ) || exit;


class Core
{
	const POST_TYPE = 'movie';
	const TAXONOMY 	= 'genre';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks(): void
	{
		add_action( 'init',                 [ $this, 'add_post_type' ] );
        add_action( 'init',                 [ $this, 'add_taxonomy' ] );
		add_action( 'after_setup_theme',    [ $this, 'set_image_sizes' ] );
	}


	public function set_image_sizes(): void
	{
		add_theme_support( 'post-thumbnails' );
		// add_image_size( 'backdrop_small', 640, 300, true );
		// add_image_size( 'backdrop', 1100, 300, true );
	}


    public function add_post_type(): void
    {
        register_post_type( 
            self::POST_TYPE, 
            [
                'description'           => 'Movies for Rob to watch (or have watched)',
                'menu_icon'             => 'dashicons-tickets-alt',
                'show_in_menu'          => true,
                'show_in_rest'          => true,
                'show_ui'               => true,
                'supports'              => [ 'title', 'editor', 'thumbnail' ],
                'taxonomies'            => [ self::TAXONOMY ],
                'labels'                => [
                    'name'                      => 'Movies',
                    'singular_name'             => 'Movie',
                    'add_new_item'              => 'Add New Movie',
                    'add_new'                   => 'Add New Movie',
                    'edit_item'                 => 'Edit Movie',
                    'new_item'                  => 'New Movie',
                    'view_item'                 => 'View Movie',
                    'view_items'                => 'View Movies',
                    'search_items'              => 'Search Movies',
                    'not_found'                 => 'No Movies found',
                    'not_found_in_trash'        => 'No Movies found in Trash',
                    'all_items'                 => 'All Movies',
                    'archives'                  => 'Movie Archives',
                    'attributes'                => 'Movie Attributes',
                    'insert_into_item'          => 'Insert into Movie',
                    'uploaded_to_this_item'     => 'Uploaded to this Movie',
                    'item_published'            => 'Movie published',
                    'item_published_privately'  => 'Movie published privately',
                    'item_reverted_to_draft'    => 'Movie reverted to draft',
                    'item_scheduled'            => 'Movie scheduled',
                    'item_updated'              => 'Movie updated'            
                ],
            ]            
        );
    }


    public function add_taxonomy(): void
    {
        $singular	= ucwords( self::TAXONOMY );
        $plural		= sprintf( '%ss', ucwords( self::TAXONOMY ) );

        register_taxonomy( 
            self::TAXONOMY, 
            self::POST_TYPE, 
            [
                'label'             => $plural,
                'show_tagcloud'     => false,
                'show_admin_column' => true,
                'labels'            => [
                    'name'          => $plural,
                    'singular_name' => $singular,                    
                ],
            ]
        );
    }

}
