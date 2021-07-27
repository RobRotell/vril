<?php


namespace Loa\Controller;


use WP_Post;


defined( 'ABSPATH' ) || exit;


class Admin
{
	const OPTION_AUTH 	= 'loa_auth';
	const OPTION_UPDATE = 'loa_last_updated';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
		$post_type = Loa()->core::POST_TYPE;

		add_action( 'admin_menu', 										[ $this, 'add_settings_page' ] );
        add_filter( 'manage_' . $post_type . '_posts_columns', 			[ $this, 'add_columns' ] );
        add_action( 'manage_' . $post_type . '_posts_custom_column', 	[ $this, 'populate_columns' ], 10, 2 );
		add_action( 'save_post_'. $post_type, 							[ $this, 'update_last_updated_value' ] );
		add_filter( 'acf/update_value', 								[ $this, 'maybe_update_last_updated_value' ], 10, 4 );		
	}


    public function add_settings_page()
    {
		add_submenu_page(
			sprintf( 'edit.php?post_type=%s', Loa()->core::POST_TYPE ),
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

        $columns['date_read']	= 'Read';
        $columns['favorited'] 	= 'Favorited';
        $columns['link_tags'] 	= 'Tags';

        return $columns;
    }


    public function populate_columns( $column, $post_id )
    {
		switch( $column ) {
			case 'date_added':
				if( !empty( $date = get_field( 'article_date_added', $post_id ) ) ) {
					echo date( 'Y-m-d', strtotime( $date ) );
				}
				break;

			case 'date_read':
				if( !empty( $date = get_field( 'article_date_read', $post_id ) ) ) {
                	echo date( 'Y-m-d', strtotime( $date ) );				
				}
				break;

			case 'favorited':
				if( !empty( get_field( 'article_is_favorite', $post_id ) ) ) {
                	echo '&#10003;';				
				}
				break;

			case 'link_tags':
				$terms = wp_get_object_terms( $post_id, Loa()->core::TAXONOMY );
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
	 * Get "last updated" value
	 *
	 * @return 	string	Last updated value
	 */	
	public static function get_last_updated(): string
	{
		return get_option( self::OPTION_UPDATE, '' );
	}

	
	/**
	 * Update "last updated" value after saving/creating movies
	 * 
	 * This value is used for checking API calls are always using latest information (especially when dealing with 
	 * transients)
	 * 
	 * @return 	void
	 */		
	public static function update_last_updated_value(): void
	{
		update_option( self::OPTION_UPDATE, time() );
	}


	/**
	 * Check that movie post was updated (and if value was changed, then update last updated value)
	 *
	 * @param	mixed 	$value 		New field value
	 * @param 	mixed 	$post_id 	ID of post being saved/updated
	 * @param 	array 	$field 		Field data
	 * @param 	mixed 	$orig_value Original field value
	 * 
	 * @return 	mixed 				New field value (no change will take place)
	 */
	public function maybe_update_last_updated_value( $value, $post_id, $field, $original_value )
	{
		if( $value !== $original_value ) {
			$post = get_post( $post_id );

			if( !empty( $post ) && $post->post_type === Loa()->core::POST_TYPE ) {
				self::update_last_updated_value();
			}
		}

		return $value;
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
