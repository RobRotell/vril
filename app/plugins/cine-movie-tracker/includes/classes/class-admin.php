<?php


namespace Movie_Tracker;


defined( 'ABSPATH' ) || exit;


class Admin
{
	const OPTION_AUTH 	= 'cine_auth';
	const OPTION_APIKEY = 'cine_apikey';
	const OPTION_UPDATE = 'cine_last_updated';


	public function __construct()
	{
		add_action( 'admin_menu', 	[ $this, 'add_settings_page' ] );
		add_action( 'save_post_' . Core::POST_TYPE, [ $this, 'update_last_updated_value' ], 10, 2 );
	}


	public function add_settings_page(): void
	{
		add_submenu_page(
			'edit.php?post_type=movie',
			'Settings',
			'Settings',
			'manage_options',
			'movie-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	
	public function render_settings_page(): void
	{
		$admin_action = 'cine_update_settings';

		if( isset( $_POST['cine_auth'] ) && isset( $_POST['cine_apikey'] ) ) {
			check_admin_referer( $admin_action );

			$auth = sanitize_text_field( $_POST['cine_auth'] );
			$apikey = sanitize_text_field( $_POST['cine_apikey'] );

			update_option( self::OPTION_AUTH, $auth );
			update_option( self::OPTION_APIKEY, $apikey );
		}

		?>
			<div class="wrap">
				<h1>Movie Settings</h1>

				<form method="POST">
					<?php wp_nonce_field( $admin_action ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<th><label for="cine_auth">Authorization</label></th>
								<td>
									<input name="cine_auth" type="text" id="cine_auth" value="<?php echo esc_attr( get_option( self::OPTION_AUTH ) ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th><label for="cine_apikey">API Key</label></th>
								<td>
									<input name="cine_apikey" type="text" id="cine_apikey" value="<?php echo esc_attr( get_option( self::OPTION_APIKEY ) ); ?>" class="regular-text" />
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


	public static function update_last_updated_value( int $post_id, WP_Post $post ): void
	{
		$title = Helper::format_title_for_comparison( $post->post_title );
		update_post_meta( $post_id, 'title_for_compare', $title );

		update_option( self::OPTION_UPDATE, time() );
	}


	public static function get_last_updated(): string
	{
		return get_option( self::OPTION_UPDATE, '' );
	}


	public static function get_auth(): string
	{
		return get_option( self::OPTION_AUTH, '' );
	}

	
	public static function get_apikey(): string
	{
		return get_option( self::OPTION_APIKEY, '' );
	}	

}
