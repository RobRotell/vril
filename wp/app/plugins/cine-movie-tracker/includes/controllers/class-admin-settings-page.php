<?php


namespace Cine\Controllers;


use Cine\Controllers\Last_Updated;
use Cine\Controllers\TMDB;
use Cine\Controllers\Tinify;
use Cine\Core\Post_Types;
use Cine\Core\Taxonomies;
use WP_Post;


defined( 'ABSPATH' ) || exit;


class Admin_Settings_Page
{
	const OPTION_TMDB_APIKEY	= 'cine_tmdb_apikey';
	const OPTION_TINIFY_APIKEY	= 'cine_tinify_apikey';
	

	public function __construct()
	{
		$this->add_wp_hooks();
	}
	
	
	private function add_wp_hooks()
	{
		add_action( 
			'admin_menu', 
			[ $this, 'add_settings_page' ] 
		);
	}	


	public function add_settings_page(): void
	{
		add_submenu_page(
			sprintf( 'edit.php?post_type=%s', Post_Types::POST_TYPE ),
			'Cine Settings',
			'Settings',
			'manage_options',
			'cine-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	
	public function render_settings_page(): void
	{
		$admin_action = 'cine_update_settings';

		if( isset( $_POST['cine_tmdb_apikey'] ) && isset( $_POST['cine_tinify_apikey'] ) ) {
			check_admin_referer( $admin_action );

			$tmdb_apikey = sanitize_text_field( $_POST['cine_tmdb_apikey'] );
			if( $tmdb_apikey !== TMDB::get_api_key() ) {
				$success_tmdb_apikey = TMDB::set_api_key( $tmdb_apikey );
			}

			$tinify_apikey = sanitize_text_field( $_POST['cine_tinify_apikey'] );
			if( $tinify_apikey !== Tinify::get_api_key() ) {
				$success_tinify_apikey = Tinify::set_api_key( $tinify_apikey );
			}

			?>

			<?php if( isset( $success_tmdb_apikey ) ): ?>
				<?php if( $success_tmdb_apikey ): ?>
					<div class="notice notice-success settings-error is-dismissible"> 
						<p><strong>TMDB API key updated!</strong></p>
						<button type="button" class="notice-dismiss"></button>
					</div>
				<?php else: ?>
					<div class="notice notice-error settings-error is-dismissible"> 
						<p><strong>Failed to save TMDB API key.</strong></p>
						<button type="button" class="notice-dismiss"></button>
					</div>
				<?php endif; ?>
			<?php endif; ?>	
			
			<?php if( isset( $success_tinify_apikey ) ): ?>
				<?php if( $success_tinify_apikey ): ?>
					<div class="notice notice-success settings-error is-dismissible"> 
						<p><strong>Tinify API key updated!</strong></p>
						<button type="button" class="notice-dismiss"></button>
					</div>
				<?php else: ?>
					<div class="notice notice-error settings-error is-dismissible"> 
						<p><strong>Failed to save Tinify API key.</strong></p>
						<button type="button" class="notice-dismiss"></button>
					</div>
				<?php endif; ?>
			<?php endif; ?>				
			
			<?php
		}

		?>
			<div class="wrap">
				<h1>Cine Settings</h1>

				<form method="POST">
					<?php wp_nonce_field( $admin_action ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<th><label for="cine_apikey">TMDB API Key</label></th>
								<td>
									<input 
										id="cine_apikey" 
										class="regular-text" 
										name="cine_tmdb_apikey" 
										type="text" 
										placeholder="••••••••••" 
									/>
								</td>
							</tr>

							<tr>
								<th><label for="cine_apikey">Tinify API Key</label></th>
								<td>
									<input 
										id="cine_apikey" 
										class="regular-text" 
										name="cine_tinify_apikey" 
										type="text" 
										placeholder="••••••••••" 
									/>
								</td>
							</tr>							

							<?php if( !empty( $updated = Last_Updated::get_timestamp() ) ): ?>
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

}
