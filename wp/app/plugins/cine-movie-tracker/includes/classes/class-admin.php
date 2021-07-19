<?php


namespace Cine;


defined( 'ABSPATH' ) || exit;


class Admin
{
	const OPTION_AUTH 	= 'cine_auth';
	const OPTION_APIKEY = 'cine_apikey';
	const OPTION_UPDATE = 'cine_last_updated';
	

	public function __construct()
	{
		$this->add_wp_hooks();
	}
	
	
	private function add_wp_hooks()
	{
		$post_type = Cine()->core::POST_TYPE;

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_filter( 'manage_' . $post_type . '_posts_columns', [ $this, 'add_columns' ] );
        add_action( 'manage_' . $post_type . '_posts_custom_column', [ $this, 'populate_columns' ], 10, 2 );		
		add_action( 'save_post_' . $post_type, [ $this, 'update_last_updated_value' ], 10, 2 );
	}	


	public function add_settings_page(): void
	{
		add_submenu_page(
			sprintf( 'edit.php?post_type=%s', Cine()->core::POST_TYPE ),
			'Cine Settings',
			'Cine Settings',
			'manage_options',
			'cine-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	
	public function render_settings_page(): void
	{
		$admin_action = 'cine_update_settings';

		if( isset( $_POST['cine_auth'] ) && isset( $_POST['cine_apikey'] ) ) {
			check_admin_referer( $admin_action );

			$auth 	= sanitize_text_field( $_POST['cine_auth'] );
			$apikey = sanitize_text_field( $_POST['cine_apikey'] );

			update_option( self::OPTION_AUTH, $auth );
			update_option( self::OPTION_APIKEY, $apikey );
		}

		?>
			<div class="wrap">
				<h1>Cine Settings</h1>

				<form method="POST">
					<?php wp_nonce_field( $admin_action ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<th><label for="cine_auth">Authorization</label></th>
								<td>
									<input name="cine_auth" type="text" id="cine_auth" value="<?php echo esc_attr( self::get_auth() ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th><label for="cine_apikey">API Key</label></th>
								<td>
									<input name="cine_apikey" type="text" id="cine_apikey" value="<?php echo esc_attr( self::get_apikey() ); ?>" class="regular-text" />
								</td>
							</tr>

							<?php if( !empty( $updated = self::get_last_updated() ) ): ?>
								<tr>
									<th><label for="cine_update">Last Updated</label></th>
									<td>
										<input 
											id="cine_update" 
											class="regular-text" 
											name="cine_update" 
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


    public function add_columns( $columns ): array
    {
        unset( $columns['date'] );
        $columns['to_watch'] = 'To Watch';

        return $columns;
    }


    public function populate_columns( $column, $post_id ): void
    {
        if( 'to_watch' === $column ) {
            if( !empty( get_field( 'to_watch', $post_id ) ) ) {
                echo '&#x2714;';
			}
        }
    }

	
	public static function update_last_updated_value( int $post_id, WP_Post $post ): void
	{
		$title = Cine()->helper::format_title_for_comparison( $post->post_title );
		update_post_meta( $post_id, 'title_for_compare', $title );

		update_option( self::OPTION_UPDATE, time() );
	}


	public static function get_last_updated(): string
	{
		return get_option( self::OPTION_UPDATE, '' );
	}


	public static function get_auth(): string
	{
		$auth = '';

		if( current_user_can( 'manage_options' ) ) {
			$auth = get_option( self::OPTION_AUTH, '' );
		}

		return $auth;
	}

	
	public static function get_apikey(): string
	{
		$auth = '';

		if( current_user_can( 'manage_options' ) ) {
			$auth = get_option( self::OPTION_APIKEY, '' );
		}

		return $auth;		
	}	

}
