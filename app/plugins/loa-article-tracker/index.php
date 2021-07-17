<?php

/**
 * Plugin Name: Loa Article Tracker
 * Plugin URI:  https://loa.robr.app
 * Description: Controls how Loa handles/displays articles
 * Version:     0.0.1
 * Author:      Rob
 * Author URI:  https://robrotell.com
 *
 * Text Domain: loa
 */

// namespace Loa_Article_Tracker; 


defined( 'ABSPATH' ) || exit;


class ArticleTracker
{
	public $plugin_path     = false;
    public $plugin_inc_path = false;
	public $plugin_url      = false;

    // plugin classes
    public $core        = null;
    public $admin       = null;
	public $endpoint    = null;
	public $helpers 	= null;


	protected static $_instance = null;
	public static function _instance()
	{
		if( !isset( self::$_instance ) ) {
			$class_name = __CLASS__;
			self::$_instance = new $class_name;
		}
		return self::$_instance;
    }
    

    public function __construct()
    {
        $this->define();
        $this->includes();
    }


    public function define()
    {
		$this->plugin_path 		= untrailingslashit( plugin_dir_path( __FILE__ ) );
		$this->plugin_inc_path 	= $this->plugin_path . '/includes';
		$this->plugin_url  		= plugin_dir_url( __FILE__ );
    }


    public function includes()
    {
        $classes = [
			'helpers',
            'core',
            'admin',
            'response', // v2
			'endpoint', // v2
			'rest-endpoint', // v1
        ];

        foreach( $classes as $class ) {
            require_once( sprintf( '%s/class-%s.php', $this->plugin_inc_path, $class ) );
        }

		$this->helpers 	= new Loa_Article_Tracker\Helpers();
        $this->core     = new Loa_Article_Tracker\Core();
		$this->admin    = new Loa_Article_Tracker\Admin();
		$this->endpoint = new Loa_Article_Tracker\Endpoint(); // v2
    }
}


function LoaArticleTracker() {
    return ArticleTracker::_instance();
}


LoaArticleTracker();