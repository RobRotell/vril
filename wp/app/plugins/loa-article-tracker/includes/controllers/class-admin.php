<?php


namespace Loa\Controller;


use WP_Post;
use Loa\Core\Last_Updated;


defined( 'ABSPATH' ) || exit;


class Admin
{
	const OPTION_AUTH 	= 'loa_auth';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
		$post_type = Loa()->post_types::POST_TYPE;

		add_action( 'admin_menu', 										[ $this, 'add_settings_page' ] );
        add_filter( 'manage_' . $post_type . '_posts_columns', 			[ $this, 'add_columns' ] );
        add_action( 'manage_' . $post_type . '_posts_custom_column',	[ $this, 'populate_columns' ], 10, 2 );
	}


    public function add_settings_page()
    {
		add_submenu_page(
			sprintf( 'edit.php?post_type=%s', Loa()->post_types::POST_TYPE ),
			'Loa Settings',
			'Settings',
			'manage_options',
			'loa-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	
	public function render_settings_page(): void
	{
		?>
			<div class="wrap">
				<h1>Loa Settings</h1>

				<form method="POST">
					<table class="form-table">
						<tbody>
							<?php if( !empty( $updated = Last_Updated::get_timestamp() ) ): ?>
								<tr>
									<th><label for="loa_update">Last Updated</label></th>
									<td>
										<input 
											id="loa_update" 
											class="regular-text" 
											name="loa_update" 
											type="text" 
											value="<?php echo esc_attr( date( 'M d, Y', $updated ) ); ?>" 
											disabled readonly 
										/>
									</td>
								</tr>
							<?php endif; ?>

						</tbody>
					</table>
				</form>
			</div>
		<?php
	}	


    public function add_columns( array $columns ): array
    {
        unset( $columns['date'] );

        $columns['read']		= 'Read';
        $columns['favorited'] 	= 'Favorited';
        $columns['link_tags'] 	= 'Tags';

        return $columns;
    }


	/**
	 * Populates column with tags associated with article
	 *
	 * @param	string 	$column 	Column name
	 * @param 	int 	$post_id 	Post ID of row
	 * 
	 * @return	void
	 */
    public function populate_columns( $column, $post_id ): void
    {
		switch( $column ) {
			case 'read':
				if( !empty( get_field( 'article_read', $post_id ) ) ) {
                	echo '&#10003;';				
				}
				break;

			case 'favorited':
				if( !empty( get_field( 'article_favorite', $post_id ) ) ) {
                	echo '&#10003;';				
				}
				break;

			case 'link_tags':
				$terms = wp_get_object_terms( $post_id, Loa()->post_types::TAXONOMY );

				if( !empty( $terms ) ) {
					$tags = [];
					foreach( $terms as $term ) {
						$tags[] = $term->name;
						asort( $tags );
					}
	
					echo implode( ', ', $tags );
				}
				break;
		}				
	}

}
