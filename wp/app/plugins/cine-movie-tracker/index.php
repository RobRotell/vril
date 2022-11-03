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
    public $auth;
    public $helpers;
    public $movies;
    public $rest_api;
    public $tinify;
    public $tmdb;

    // core subclasses
    public $admin_columns;
    public $admin_settings_page;
    public $last_updated;
    public $post_types;
    public $taxonomy_genres;
    public $taxonomy_production_companies;
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
        // abstracts
        require_once( self::$plugin_path_inc . 'abstracts/abstract-movie.php' );

        // controllers
        require_once( self::$plugin_path_inc . 'controllers/class-auth.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-helpers.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-movies.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-rest-api.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-tinify.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-tmdb.php' );
        require_once( self::$plugin_path_inc . 'controllers/class-genres.php' );
        
        // core
        require_once( self::$plugin_path_inc . 'core/class-admin-columns.php' );
        require_once( self::$plugin_path_inc . 'core/class-admin-settings-page.php' );
        require_once( self::$plugin_path_inc . 'core/class-last-updated.php' );
        require_once( self::$plugin_path_inc . 'core/class-post-types.php' );
        require_once( self::$plugin_path_inc . 'core/class-taxonomy-genres.php' );
        require_once( self::$plugin_path_inc . 'core/class-taxonomy-production-companies.php' );
        require_once( self::$plugin_path_inc . 'core/class-transients.php' );

        // models
        require_once( self::$plugin_path_inc . 'models/class-frontend-movie.php' );
        require_once( self::$plugin_path_inc . 'models/class-production-company.php' );
        require_once( self::$plugin_path_inc . 'models/class-tmdb-movie-result.php' );
        require_once( self::$plugin_path_inc . 'models/class-movie-to-add.php' );
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
        $this->auth     = new Cine\Controllers\Auth;
        $this->helpers  = new Cine\Controllers\Helpers;
        $this->movies   = new Cine\Controllers\Movies;
        $this->rest_api = new Cine\Controllers\REST_API;
        $this->tinify   = new Cine\Controllers\Tinify;
        $this->tmdb     = new Cine\Controllers\TMDb;
        $this->genres   = new Cine\Controllers\Genres;
        
        $this->admin_columns                    = new Cine\Core\Admin_Columns;
        $this->admin_settings_page              = new Cine\Core\Admin_Settings_Page;
        $this->last_updated                     = new Cine\Core\Last_Updated;
        $this->post_types                       = new Cine\Core\Post_Types;
        $this->taxonomy_genres                  = new Cine\Core\Taxonomy_Genres;
        $this->taxonomy_production_companies    = new Cine\Core\Taxonomy_Production_Companies;
        $this->transients                       = new Cine\Core\Transients;
    }

}


function Cine() {
    return Cine::getInstance();
}


Cine();

