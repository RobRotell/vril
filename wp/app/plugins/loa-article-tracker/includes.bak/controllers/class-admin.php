<?php


namespace Loa\Controller;


use WP_Post;


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
		$admin_action = 'loa_update_settings';

		if( isset( $_POST['loa_auth'] ) ) {
			check_admin_referer( $admin_action );

			$auth = sanitize_text_field( $_POST['loa_auth'] );
			if( $auth !== self::get_auth() ) {
				$success = self::set_auth( $auth );
			}

			?>

			<?php if( isset( $success ) && $success ): ?>
				<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"> 
					<p><strong>Settings updated!</strong></p>
					<button type="button" class="notice-dismiss"></button>
				</div>
			<?php else: ?>
				<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible"> 
					<p><strong>Failed to save settings.</strong></p>
					<button type="button" class="notice-dismiss"></button>
				</div>
			<?php endif; ?>

			<?php
		}

		?>
		
			<div class="wrap">
				<h1>Loa Settings</h1>

				<form method="POST">
					<?php wp_nonce_field( $admin_action ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<th><label for="loa_auth">Authorization</label></th>
								<td>
									<input 
										id="loa_auth" 
										class="regular-text" 
										name="loa_auth" 
										type="text" 
										value="••••••••••" 
									/>
								</td>
							</tr>

							<?php if( !empty( $updated = self::get_last_updated() ) ): ?>
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
					<?php submit_button( 'Update' ); ?>
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


	/**
	 * Check if passed string matches authorization code
	 *
	 * @param	string	$arg 	Potential auth code
	 * @return 	bool 			True, if arg matches auth code
	 */	
	public static function check_auth( string $arg = '' ): bool
	{
		$arg = Loa()->helper::hash( $arg );

		return $arg === self::get_auth();
	}	


	/**
	 * Get current auth code
	 *
	 * @return 	string 	Auth code
	 */		
	public static function get_auth(): string
	{
		return get_option( self::OPTION_AUTH, '' );
	}


	/**
	 * Set new auth code
	 *
	 * @param	string	$auth 	New auth code
	 * @return 	bool 			True, if new auth code was saved
	 */		
	public static function set_auth( string $auth ): bool
	{
		if( current_user_can( 'manage_options' ) ) {
			$auth = Loa()->helper::hash( $auth );

			return update_option( self::OPTION_AUTH, $auth );
		}
		
		return false;
	}

}
