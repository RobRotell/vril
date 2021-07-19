<?php


namespace Loa;


defined( 'ABSPATH' ) || exit;


class Admin
{
	const OPTION_AUTH = 'loa_auth';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
		$post_type = Loa()->core::POST_TYPE;

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

        add_filter( 'manage_' . $post_type . '_posts_columns', [ $this, 'add_columns' ] );
        add_action( 'manage_' . $post_type . '_posts_custom_column', [ $this, 'populate_columns' ], 10, 2 );		
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
			$success = self::set_auth( $auth );

			?>

			<?php if( $success ): ?>
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
										type="password" 
										value="****************" 
									/>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button( 'Update' ); ?>
				</form>
			</div>
		<?php
	}	



	// public function add_columns( array $columns, int $post_id ): array
	// {
    //     // target only articles
    //     if( 'article' !==get_post_type( $post_id ) !== 'article' ) {

	// 	}
    //         return;

    //     // check if article has added date
    //     if( empty( get_field( 'article_date_added', $post_id ) ) ) {

    //         // save as now
    //         $now = new DateTime();
    //         $now = $now->format( 'Y-m-d' );

    //         update_field( 'article_date_added', $now, $post_id );
    //     }
    // }


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


	public static function get_auth(): string
	{
		$auth = '';

		if( current_user_can( 'manage_options' ) ) {
			$auth = get_option( self::OPTION_AUTH, '' );
		}

		return $auth;
	}


	public static function set_auth( string $auth ): bool
	{
		if( current_user_can( 'manage_options' ) ) {
			$auth = Loa()->helper::hash( $auth );

			return update_option( self::OPTION_AUTH, $auth );
		}
		
		return false;
	}

}
