<?php


namespace Loa\Controller;


defined( 'ABSPATH' ) || exit;


class Core
{
    const POST_TYPE = 'article';
    const TAXONOMY  = 'article-cat';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
        add_action( 'init', [ $this, 'add_post_type' ] );
        add_action( 'init', [ $this, 'add_taxonomy' ] );		
	}

	
    public function add_post_type()
    {
        register_post_type( 
            self::POST_TYPE, 
            [
                'description'           => 'Articles for Rob to read (or have read)',
                'menu_icon'             => 'dashicons-admin-links',
                'show_in_menu'          => true,
                'show_in_rest'          => true,
                'show_ui'               => true,
                'supports'              => [ 'title', 'editor' ],
                'taxonomies'            => [ self::TAXONOMY ],
                'labels'                => [
                    'name'                      => 'Articles',
                    'singular_name'             => 'Article',
                    'add_new_item'              => 'Add New Article',
                    'add_new'                   => 'Add New Article',
                    'edit_item'                 => 'Edit Article',
                    'new_item'                  => 'New Article',
                    'view_item'                 => 'View Article',
                    'view_items'                => 'View Articles',
                    'search_items'              => 'Search Articles',
                    'not_found'                 => 'No Articles found',
                    'not_found_in_trash'        => 'No Articles found in Trash',
                    'all_items'                 => 'All Articles',
                    'archives'                  => 'Article Archives',
                    'attributes'                => 'Article Attributes',
                    'insert_into_item'          => 'Insert into Article',
                    'uploaded_to_this_item'     => 'Uploaded to this Article',
                    'item_published'            => 'Article published',
                    'item_published_privately'  => 'Article published privately',
                    'item_reverted_to_draft'    => 'Article reverted to draft',
                    'item_scheduled'            => 'Article scheduled',
                    'item_updated'              => 'Article updated'            
                ],
            ]
        );
    }


    public function add_taxonomy()
    {
        register_taxonomy( 
            self::TAXONOMY, 
            self::POST_TYPE, 
            [
                'label'         => 'Tags',
                'show_in_rest'  => true,
                'show_tagcloud' => false,
                'labels'        => [
                    'name'          => 'Tags',
                    'singular_name' => 'Tag',
                ],                
            ]
        );
    }
    
}
