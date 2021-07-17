<?php

/**
 * Plugin Name: Cine Movie Tracker
 * Plugin URI:  https://vril.robr.app
 * Description: Controls how Cine returns/dislays movies
 * Version:     0.0.1
 * Author:      Rob
 * Author URI:  https://robrotell.com
 *
 * Text Domain: cine
 */


defined( 'ABSPATH' ) || exit;


class Movie_Tracker
{
	protected static $_instance = null;


    public static $plugin_url       = false;
    public static $plugin_path      = false;
    public static $plugin_path_inc  = false;


    // subclasses
    protected $core         = false;
    protected $endpoint     = false;
    protected $admin        = false;


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
        $this->set_sub_classes();
    }


    private function define(): void
    {
        self::$plugin_url 		= trailingslashit( plugin_dir_url( __FILE__ ) );
        self::$plugin_path 		= trailingslashit( plugin_dir_path( __FILE__ ) );
        self::$plugin_path_inc 	= trailingslashit( sprintf( '%sincludes/', self::$plugin_path ) );
    }


    private function includes(): void
    {
        require_once( self::$plugin_path . '/includes/classes/class-helpers.php' );
        require_once( self::$plugin_path . '/includes/classes/class-core.php' );
        require_once( self::$plugin_path . '/includes/classes/class-endpoint.php' );

        require_once( self::$plugin_path . '/includes/class-fetcher.php' );
        require_once( self::$plugin_path . '/includes/classes/class-admin.php' );

        // models
        // require_once( self::$plugin_path . '/includes/class-search-result.php' );
        // require_once( self::$plugin_path . '/includes/class-raw-movie.php' );
        // require_once( self::$plugin_path . '/includes/class-display-movie.php' );
    }


    private function set_sub_classes(): void
    {
        $this->core         = new Movie_Tracker\Core();
        $this->endpoint     = new Movie_Tracker\Endpoint();
        $this->admin        = new Movie_Tracker\Admin();
    }

}


add_action( 'plugins_loaded', [ 'Movie_Tracker', '_instance' ] );