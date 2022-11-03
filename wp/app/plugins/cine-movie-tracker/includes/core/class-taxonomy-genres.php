<?php


namespace Cine\Core;


defined('ABSPATH') || exit;


class Taxonomy_Genres
{
	const TAXONOMY_KEY			= 'genre';
	const TAXONOMY_KEY_PLURAL	= 'genres';
	const TAXONOMY_LABEL        = 'Genre';
	const TAXONOMY_LABEL_PLURAL = 'Genres';	


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
		add_action(
			'init', 
			[ $this, 'add_taxonomy' ]
		);
	}


	public function add_taxonomy()
	{
		register_taxonomy(
			self::TAXONOMY_KEY,
			Post_Types::POST_TYPE_KEY,
			[
				'label'             => self::TAXONOMY_KEY_PLURAL,
				'show_tagcloud'     => false,
				'show_admin_column' => true,
				'labels'            => [
					'name'          => ucwords( self::TAXONOMY_LABEL_PLURAL ),
					'singular_name' => ucwords( self::TAXONOMY_LABEL ),
				],
			]
		);
	}
}
