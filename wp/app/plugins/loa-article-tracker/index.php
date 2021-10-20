<?php

/**
 * Plugin Name: Loa
 * Plugin URI:  https://vril.robr.app
 * Description: Controls tracking, storage, and API for articles
 * Version:     3.0.0
 * Author:      Rob
 * Author URI:  https://robrotell.dev
 *
 * Text Domain: loa
 */


defined( 'ABSPATH' ) || exit;


class Loa
{
	protected static $_instance = null;


    public static $plugin_url       = false;
    public static $plugin_path      = false;
    public static $plugin_path_inc  = false;


    // subclasses
    public $helper      = null;
    public $post_types  = null;
    public $update_time = null;
    public $admin       = null;
    public $api         = null;
    

	public static function _instance(): self
	{
        if( null === self::$_instance ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    

    public function __construct()
    {
        $this->define();
        $this->includes();
        $this->add_wp_hooks();
    }


    private function define(): void
    {
        self::$plugin_url 		= trailingslashit( plugin_dir_url( __FILE__ ) );
        self::$plugin_path 		= trailingslashit( plugin_dir_path( __FILE__ ) );
        self::$plugin_path_inc 	= trailingslashit( sprintf( '%sincludes/', self::$plugin_path ) );
    }


    private function includes(): void
    {
        // abstracts
        require_once( self::$plugin_path_inc . 'abstracts/abstract-class-endpoint.php' );

        // models
        require_once( self::$plugin_path_inc . 'models/class-api-response.php' );        
        require_once( self::$plugin_path_inc . 'models/class-article-block.php' );
        require_once( self::$plugin_path_inc . 'models/class-new-article.php' );

        // core
        require_once( self::$plugin_path_inc . 'core/class-helper.php' );
        require_once( self::$plugin_path_inc . 'core/class-post-types.php' );
        require_once( self::$plugin_path_inc . 'core/class-update-time.php' );

        // controllers
        require_once( self::$plugin_path_inc . 'controllers/class-api.php' );

        // admin-only
        if( is_admin() ) {
            require_once( self::$plugin_path_inc . 'controllers/class-admin.php' );
        }        
    }


	private function add_wp_hooks(): void
	{
		add_action( 'plugins_loaded', [ $this, 'load_classes' ] );
	}    


    public function load_classes(): void
    {
        $this->helper       = new Loa\Core\Helper();
        $this->post_types   = new Loa\Core\Post_Types();
        $this->last_updated = new Loa\Core\Last_Updated();
        $this->api          = new Loa\Controller\API();

        if( is_admin() ) {
            $this->admin = new Loa\Controller\Admin();
        }
    }
}


function Loa() {
    return Loa::_instance();
}

Loa();
