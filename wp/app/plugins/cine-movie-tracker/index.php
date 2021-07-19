<?php

/**
 * Plugin Name: Cine
 * Plugin URI:  https://vril.robr.app
 * Description: Controls tracking, storage, and API for movies
 * Version:     0.0.1
 * Author:      Rob
 * Author URI:  https://robrotell.dev
 *
 * Text Domain: cine
 */


defined( 'ABSPATH' ) || exit;


class Cine
{
	protected static $_instance = null;


    public static $plugin_url       = false;
    public static $plugin_path      = false;
    public static $plugin_path_inc  = false;


    // subclasses
    public $helper   = null;
    public $core     = null;
    public $endpoint = null;
    public $admin    = null;


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
        require_once( self::$plugin_path_inc . 'classes/class-helper.php' );
        require_once( self::$plugin_path_inc . 'classes/class-core.php' );
        require_once( self::$plugin_path_inc . 'classes/class-endpoint.php' );

        // require_once( self::$plugin_path_inc . 'class-fetcher.php' );
        require_once( self::$plugin_path_inc . 'classes/class-admin.php' );

        // models
        // require_once( self::$plugin_path . '/class-search-result.php' );
        // require_once( self::$plugin_path . '/class-raw-movie.php' );
        // require_once( self::$plugin_path . '/class-display-movie.php' );
    }


	private function add_wp_hooks(): void
	{
        add_action( 'plugins_loaded', [ $this, 'load_classes' ] );
	}    


    public function load_classes(): void
    {
        $this->helper   = new Cine\Helper();
        $this->core     = new Cine\Core();
        $this->endpoint = new Cine\Endpoint();
        $this->admin    = new Cine\Admin();
    }

}


function Cine() {
    return Cine::_instance();
}


Cine();

