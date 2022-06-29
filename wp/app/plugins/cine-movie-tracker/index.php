<?php


/**
 * Plugin Name: Cine
 * Plugin URI:  https://vril.robr.app
 * Description: Controls tracking, storage, and API for movies
 * Version:     2.0.0
 * Author:      Rob
 * Author URI:  https://robrotell.dev
 *
 * Text Domain: cine
 */


defined( 'ABSPATH' ) || exit;


final class Cine
{
	protected static $_instance = null;


    public static $plugin_url       = false;
    public static $plugin_path      = false;
    public static $plugin_path_inc  = false;


    // controller subclasses
    public $admin_columns;
    public $admin_settings_page;
    public $auth_tokens;
    public $helpers;
    public $last_updated;
    public $rest_api;
    public $tinify;
    public $tmdb;

    // core subclasses
    public $post_types;
    public $taxonomies;
    public $transients;


	public static function getInstance(): self
	{
        if( null === self::$_instance ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    

    private function __construct()
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
        // require_once( self::$plugin_path_inc . 'models/class-movie-block.php' );
        // require_once( self::$plugin_path_inc . 'models/class-search-result.php' );
        // require_once( self::$plugin_path_inc . 'models/class-new-movie.php' );
        // require_once( self::$plugin_path_inc . 'models/class-api-response.php' );

        // controllers
        require_once( self::$plugin_path_inc . 'controllers/class-rest-api.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-admin-columns.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-admin-settings-page.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-auth-tokens.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-helpers.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-last-updated.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-tinify.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-tmdb.php' );
        
        // core
        require_once( self::$plugin_path_inc . 'core/class-post-types.php' );        
        require_once( self::$plugin_path_inc . 'core/class-taxonomies.php' );
        require_once( self::$plugin_path_inc . 'core/class-transients.php' );

        // models
        require_once( self::$plugin_path_inc . 'models/class-movie-block.php' );
        require_once( self::$plugin_path_inc . 'models/class-tmdb-movie.php' );
    }


	private function add_wp_hooks(): void
	{
        add_action( 
            'plugins_loaded', 
            [ $this, 'load_classes' ] 
        );
	}    


    public function load_classes(): void
    {
        $this->admin_columns        = new Cine\Controllers\Admin_Columns;
        $this->admin_settings_page  = new Cine\Controllers\Admin_Settings_Page;
        $this->auth_tokens          = new Cine\Controllers\Auth_Tokens;
        $this->helpers              = new Cine\Controllers\Helpers;
        $this->last_updated         = new Cine\Controllers\Last_Updated;
        $this->rest_api             = new Cine\Controllers\REST_API;
        $this->tinify               = new Cine\Controllers\Tinify;
        $this->tmdb                 = new Cine\Controllers\TMDb;

        $this->post_types           = new Cine\Core\Post_Types;
        $this->taxonomies           = new Cine\Core\Taxonomies;
        $this->transients           = new Cine\Core\Transients;
    }

}


function Cine() {
    return Cine::getInstance();
}


Cine();

