<?php


namespace Cine\Controllers;


use Cine\Core\Post_Types;
use Cine\Core\Taxonomies;
use WP_Post;


defined( 'ABSPATH' ) || exit;


class Admin_Columns
{
	public function __construct()
	{
		$this->add_wp_hooks();
	}
	
	
	private function add_wp_hooks()
	{
		$post_type = Post_Types::POST_TYPE;

        add_action( 'manage_' . $post_type . '_posts_custom_column', 	[ $this, 'populate_columns' ], 10, 2 );		
		add_filter( 'manage_' . $post_type . '_posts_columns', 			[ $this, 'add_columns' ] );
	}	


	/**
	 * Add custom columns to WP admin
	 *
	 * @param	array 	$columns 	Admin columns
	 * @return 	array 				Admin columns
	 */
    public function add_columns( $columns ): array
    {
        unset( $columns['date'] );
        $columns['to_watch'] = 'To Watch';

        return $columns;
    }


	/**
	 * Populate custom admin columns
	 *
	 * @param	string	$column 	Column name
	 * @param 	int 	$post_id 	Post ID for post in row
	 * 
	 * @return 	void
	 */	
    public function populate_columns( $column, $post_id ): void
    {
        if( 'to_watch' === $column ) {
            if( !empty( get_field( 'to_watch', $post_id ) ) ) {
                echo '&#x2714;';
			}
        }
    }

}
