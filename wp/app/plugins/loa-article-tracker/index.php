<?php

/**
 * Plugin Name: Loa
 * Plugin URI:  https://vril.robr.app
 * Description: Controls tracking, storage, and API for articles
 * Version:     0.1.0
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
        require_once( self::$plugin_path_inc . 'classes/class-admin.php' );
    }


	private function add_wp_hooks(): void
	{
		add_action( 'plugins_loaded', [ $this, 'load_classes' ] );
	}    


    public function load_classes(): void
    {
        $this->helper   = new Loa\Helper();
        $this->core     = new Loa\Core();
        $this->endpoint = new Loa\Endpoint();
        $this->admin    = new Loa\Admin();
    }
}


function Loa() {
    return Loa::_instance();
}


Loa();
