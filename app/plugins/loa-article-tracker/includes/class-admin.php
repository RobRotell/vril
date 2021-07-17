<?php

namespace Loa_Article_Tracker;


defined( 'ABSPATH' ) || exit;


class Admin
{
	public static $asset_handle = 'LoaArticleTrackerAssets';

	public static $option_name 	= 'loa_article_tracker_auth_key';

	public static $nonce_action = 'update_loa_article_tracker_auth_key';
	public static $nonce_name 	= '_loa_article_tracker_auth_nonce';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
		add_action( 'admin_init', 	[ $this, 'add_settings_page' ] );
		add_action( 'admin_menu', 	[ $this, 'add_settings_page_to_menu' ] );

		add_action( 'admin_enqueue_scripts', 	[ $this, 'register_scripts' ] );

        add_filter( 'manage_article_posts_columns',         [ $this, 'add_columns' ] );
        add_action( 'manage_article_posts_custom_column',   [ $this, 'populate_columns' ], 10, 2 );        

		add_action( 'acf/save_post',	[ $this, 'save_article' ], 99 );
		
		add_action( 'wp_ajax_loa_article_tracker_update_auth_key', 	[ $this, 'update_auth_key' ] );
	}


    public function save_article( $post_id )
    {
        // target only articles
        if( get_post_type( $post_id ) !== 'article' )
            return;

        // check if article has added date
        if( empty( get_field( 'article_date_added', $post_id ) ) ) {

            // save as now
            $now = new DateTime();
            $now = $now->format( 'Y-m-d' );

            update_field( 'article_date_added', $now, $post_id );
        }
    }


    public function add_columns( $columns )
    {
        unset( $columns['date'] );
        $columns['date_added'] = 'Added';
        $columns['date_read'] = 'Read';
        $columns['favorited'] = 'Favorited';
        $columns['link_tags'] = 'Tags';

        return $columns;
    }


    public function populate_columns( $column, $post_id )
    {
        if( $column === 'date_added' ) {
            if( !empty( $date = get_field( 'article_date_added', $post_id ) ) )
                echo date( 'Y-m-d', strtotime( $date ) );
        
        } elseif( $column === 'date_read' ) {
            if( !empty( $date = get_field( 'article_date_read', $post_id ) ) )
                echo date( 'Y-m-d', strtotime( $date ) );
        
        } elseif( $column === 'favorited' ) {
            if( !empty( get_field( 'article_is_favorite', $post_id ) ) )
                echo '&#10003;';
        
        } elseif( $column === 'link_tags' ) {
            $terms = wp_get_object_terms( $post_id, 'article-cat' );
            if( !empty( $terms ) ) {
                $tags = [];
                foreach( $terms as $term ) {
                    $tags[] = $term->name;
                    asort( $tags );
                }

                echo implode( ', ', $tags );
            }
        }
	}


	public function add_settings_page()
	{
		register_setting( 
			self::$option_name, 
			self::$option_name,
			[
				'type'				=> 'string',
				'description'		=> 'Auth key for authenticated users',
				'sanitize_callback'	=> [ $this, 'sanitize_auth_key' ],
				'show_in_rest'		=> true,
				'default'			=> null
			]
		);
	}


	public function sanitize_auth_key( $arg )
	{
		if( !empty( $arg ) && is_string( $arg ) ) {
			$arg = sanitize_text_field( $arg );
			$arg = strtolower( $arg );
			$arg = preg_replace( '/[^A-Za-z0-9]/', '', $arg );

			if( 8 < strlen( $arg ) ) {
				return md5( $arg );
			}
		}
	}	


	public function add_settings_page_to_menu() {
		add_submenu_page(
			'edit.php?post_type=article',
			'Article Tracker Settings',
			'Settings',
			'manage_options',
			'article-tracker-settings',
			[ $this, 'render_settings_page' ]
		);
	}


	public function render_settings_page()
	{
		if( !current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script( self::$asset_handle );
		include_once( LoaArticleTracker()->plugin_inc_path . '/templates/admin-settings.php' );
	}


	public function register_scripts()
	{
		wp_register_script(
			'LoaVue',
			'https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js',
			// 'https://cdn.jsdelivr.net/npm/vue@2',
			null,
			null,
			true
		);

		wp_register_script( 
			self::$asset_handle, 
			LoaArticleTracker()->plugin_url . '/src/js/app.js',
			[ 'LoaVue' ], 
			filemtime( LoaArticleTracker()->plugin_path . '/src/js/app.js' ),
			true
		);

		wp_localize_script(
			self::$asset_handle, 
			'LoaArticleTrackerSettings',
			[
				'ajax_url'	=> admin_url( 'admin-ajax.php' ),
			]
		);
	}


	public function update_auth_key()
	{
		if( !isset( $_POST[ self::$nonce_name ] ) || !wp_verify_nonce( $_POST[ self::$nonce_name ], self::$nonce_action ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// grab submitted auth key
		$auth_key = sanitize_text_field( (string)$_POST['auth_key'] );

		// ensure it's a decent length 
		if( 8 > strlen( preg_replace( '/[^A-Za-z0-9]/', '', $auth_key ) ) ) { 
			wp_send_json_error( 'Authorization key should be at least ten characters (excluding white space)' );
		}

		$updated = update_option( self::$option_name, $auth_key );
		if( !$updated ) {
			wp_send_json_error( 'Failed to update authorization key' );
		} else {
			wp_send_json_success( 'Successfully updated authorization key!' );
		}
	}


}