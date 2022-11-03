<?php


namespace Cine\Core;


use Cine\Core\Taxonomy_Genres;
use Cine\Core\Taxonomy_Production_Companies;


defined('ABSPATH') || exit;


class Post_Types
{
	const POST_TYPE_KEY				= 'movie';
	const POST_TYPE_LABEL 			= 'Movie';
	const POST_TYPE_LABEL_PLURAL 	= 'Movies';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
		add_action(
			'init', 
			[ $this, 'add_post_type' ]
		);
	}


	public function add_post_type()
	{
		register_post_type(
			self::POST_TYPE_KEY,
			[
				'description'           => 'Movies for Rob to watch (or have watched)',
				'menu_icon'             => 'dashicons-tickets-alt',
				'menu_position'         => 50,
				'show_in_menu'          => true,
				'show_in_rest'          => true,
				'show_ui'               => true,
				'supports'              => [ 'title', 'editor', 'thumbnail' ],
				'taxonomies'            => [ Taxonomy_Genres::TAXONOMY_KEY, Taxonomy_Production_Companies::TAXONOMY_KEY ],
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
}
