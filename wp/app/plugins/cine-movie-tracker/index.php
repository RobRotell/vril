<?php

/**
 * Plugin Name: Cine
 * Plugin URI:  https://vril.robr.app
 * Description: Controls tracking, storage, and API for movies
 * Version:     1.0.0
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
    public $tmdb        = null;
    public $tinify      = null;
    public $helper      = null;
    public $core        = null;
    public $admin       = null;
    public $api         = null;
    public $endpoint    = null;


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
        // models
        require_once( self::$plugin_path_inc . 'models/class-movie-block.php' );
        require_once( self::$plugin_path_inc . 'models/class-search-result.php' );
        require_once( self::$plugin_path_inc . 'models/class-new-movie.php' );
        require_once( self::$plugin_path_inc . 'models/class-api-response.php' );

        // external APIs
        require_once( self::$plugin_path_inc . 'apis/class-tinify.php' );
        require_once( self::$plugin_path_inc . 'apis/class-tmdb.php' );
        
        // controllers
        require_once( self::$plugin_path_inc . 'controllers/class-helper.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-core.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-admin.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-api.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-endpoint.php' );
    }


	private function add_wp_hooks(): void
	{
        add_action( 'plugins_loaded', [ $this, 'load_classes' ] );
	}    


    public function load_classes(): void
    {
        $this->tmdb     = new Cine\Api\TMDB();
        $this->tinify   = new Cine\Api\Tinify();
        
        $this->helper   = new Cine\Controller\Helper();
        $this->core     = new Cine\Controller\Core();
        $this->admin    = new Cine\Controller\Admin();
        $this->api      = new Cine\Controller\API();
        $this->endpoint = new Cine\Controller\Endpoint();
    }

}


function Cine() {
    return Cine::_instance();
}


Cine();

