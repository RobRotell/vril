<?php


namespace Cine\Controller;


use WP_Post;


defined( 'ABSPATH' ) || exit;


class Admin
{
	const OPTION_AUTH 			= 'cine_auth';
	const OPTION_TMDB_APIKEY	= 'cine_tmdb_apikey';
	const OPTION_TINIFY_APIKEY	= 'cine_tinify_apikey';

	const OPTION_UPDATE			= 'cine_last_updated';
	

	public function __construct()
	{
		$this->add_wp_hooks();
	}
	
	
	private function add_wp_hooks()
	{
		$post_type = Cine()->core::POST_TYPE;

		add_action( 'admin_menu', 										[ $this, 'add_settings_page' ] );
        add_action( 'manage_' . $post_type . '_posts_custom_column', 	[ $this, 'populate_columns' ], 10, 2 );		
		add_action( 'save_post_' . $post_type, 							[ $this, 'update_last_updated_value' ], 10, 2 );
        
		add_filter( 'manage_' . $post_type . '_posts_columns', 			[ $this, 'add_columns' ] );
		add_filter( 'acf/update_value', 								[ $this, 'maybe_update_last_updated_value' ], 10, 4 );
	}	


	public function add_settings_page(): void
	{
		add_submenu_page(
			sprintf( 'edit.php?post_type=%s', Cine()->core::POST_TYPE ),
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

		if( isset( $_POST['cine_auth'] ) && isset( $_POST['cine_tmdb_apikey'] ) && isset( $_POST['cine_tinify_apikey'] ) ) {
			check_admin_referer( $admin_action );

			$auth = sanitize_text_field( $_POST['cine_auth'] );
			if( !self::check_auth( $auth ) ) {
				$success_auth = self::set_auth( $auth );
			}

			$tmdb_apikey = sanitize_text_field( $_POST['cine_tmdb_apikey'] );
			if( $tmdb_apikey !== self::get_tmdb_apikey() ) {
				$success_tmdb_apikey = self::set_tmdb_apikey( $tmdb_apikey );
			}

			$tinify_apikey = sanitize_text_field( $_POST['cine_tinify_apikey'] );
			if( $tinify_apikey !== self::get_tinify_apikey() ) {
				$success_tinify_apikey = self::set_tinify_apikey( $tinify_apikey );
			}

			?>
			
			<?php if( isset( $success_auth ) ): ?>
				<?php if( $success_auth ): ?>
					<div class="notice notice-success settings-error is-dismissible"> 
						<p><strong>Authorization code updated!</strong></p>
						<button type="button" class="notice-dismiss"></button>
					</div>
				<?php else: ?>
					<div class="notice notice-error settings-error is-dismissible"> 
						<p><strong>Failed to save authorization code.</strong></p>
						<button type="button" class="notice-dismiss"></button>
					</div>
				<?php endif; ?>
			<?php endif; ?>

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
								<th><label for="cine_auth">Authorization</label></th>
								<td>
									<input 
										id="cine_auth" 
										class="regular-text" 
										name="cine_auth" 
										type="text" 
										placeholder="••••••••••"
									/>
								</td>
							</tr>
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

			if( !empty( $post ) && $post->post_type === Cine()->core::POST_TYPE ) {
				self::update_last_updated_value( $post_id, $post );
			}
		}

		return $value;
	}

	
	/**
	 * Update "last updated" value after saving/creating movies
	 * 
	 * This value is used for checking API calls are always using latest information (especially when dealing with 
	 * transients)
	 *
	 * @param	int 	$post_id 	Post ID for post being saved/updated
	 * @param 	WP_Post $post 		Post being saved/updated
	 * 
	 * @return 	void
	 */	
	public static function update_last_updated_value( int $post_id, WP_Post $post ): void
	{
		$title = Cine()->helper::format_title_for_comparison( $post->post_title );
		update_post_meta( $post_id, 'title_for_compare', $title );

		update_option( self::OPTION_UPDATE, time() );
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
	 * Check if passed string matches authorization code
	 *
	 * @param	string	$arg 	Potential auth code
	 * @return 	bool 			True, if arg matches auth code
	 */	
	public static function check_auth( string $arg = '' ): bool
	{
		$arg = Cine()->helper::hash( $arg );

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
	private static function set_auth( string $auth ): bool
	{
		if( current_user_can( 'manage_options' ) ) {
			$auth = Cine()->helper::hash( $auth );

			return update_option( self::OPTION_AUTH, $auth );
		}
		
		return false;
	}	

	
	/**
	 * Get API key for TMDB
	 *
	 * @return 	string 	API key
	 */	
	public static function get_tmdb_apikey(): string
	{
		return get_option( self::OPTION_TMDB_APIKEY, '' );
	}



	/**
	 * Set new API key
	 *
	 * @param	string	$apikey 	New API key 	
	 * @return 	bool 				True, if new API key was saved
	 */	
	private static function set_tmdb_apikey( string $apikey ): bool
	{
		if( current_user_can( 'manage_options' ) ) {
			return update_option( self::OPTION_TMDB_APIKEY, $apikey );
		}
		
		return false;
	}	

	
	/**
	 * Get API key for TMDB
	 *
	 * @return 	string 	API key
	 */	
	public static function get_tinify_apikey(): string
	{
		return get_option( self::OPTION_TINIFY_APIKEY, '' );
	}	


	/**
	 * Set new API key
	 *
	 * @param	string	$apikey 	New API key 	
	 * @return 	bool 				True, if new API key was saved
	 */	
	private static function set_tinify_apikey( string $apikey ): bool
	{
		if( current_user_can( 'manage_options' ) ) {
			return update_option( self::OPTION_TINIFY_APIKEY, $apikey );
		}
		
		return false;
	}			

}
