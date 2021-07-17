<?php

namespace Loa_Article_Tracker;

use Exception;

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );


defined( 'ABSPATH' ) || exit;


class Endpoint
{
    private static $bypass = [
        'medium.com',
        'uxdesign.cc'
    ];

	protected static $instance = null;
	public static function instance()
	{
		if( !isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
    }


    public function __construct()
    {
        $actions = [
            'get_everything',
            'get_articles',
            'get_tags',
            'save_article',
            'read_article'
        ];

        foreach( $actions as $action ) {
            add_action( 'wp_ajax_loa_' . $action,         [ $this, $action ] );
            add_action( 'wp_ajax_nopriv_loa_' . $action,  [ $this, $action ] );
        }
    }


    public function get_articles( $is_ajax = true )
    {
        $this->validate( $_POST );

        $posts = get_posts(
            [
                'post_type'         => 'article',
                'posts_per_page'    => -1
            ]
        );

        $articles = [];
        foreach( $posts as $post ) {
            $article = new Article( $post->ID );
            if( $article->is_unread() )
                $articles[] = $article->get();
        }

        if( $is_ajax ) {
            wp_send_json_success( $articles );
        } else {
            return $articles;
        }
    }


    public function get_tags( $is_ajax = true )
    {
        $this->validate( $_POST );

        $terms = get_terms(
            [
                'taxonomy'      => 'article-cat',
                'hide_empty'    => false
            ]
        );

        $tags = [];
        foreach( $terms as $term ) {
            $tags[] = [
                'id'    => $term->term_id,
                'name'  => $term->name
            ];
        }

        if( $is_ajax ) {
            wp_send_json_success( $tags );
        } else {
            return $tags;
        }
    }    


    private function validate( $post )
    {
        if( !isset( $_POST['auth'] ) )
            wp_send_json_error( 'No authorization provided' );

        if( md5( $_POST['auth'] ) !== LOA()->auth )
            wp_send_json_error( 'Invalid authorization' );
    }


    public function save_article()
    {
        $this->validate( $_POST );
        
        try {

            $data = [];
            if( isset( $_POST['data'] ) && !empty( $_POST['data'] ) ) {
                $data = stripslashes( $_POST['data'] );
                $data = json_decode( $data, true );
            }

            if( !isset( $data['link_name'] ) || empty( $data['link_name'] ) ) {
                throw new Exception( 'Missing link' );
            } else {
                $link = $data['link_name'];
            }

            // check for valid link
            $link = filter_var( $link, FILTER_SANITIZE_URL );
            if( empty( $link ) )
                throw new Exception( 'Link is not a valid URL' );

            // use only HTTPS links
            $link = str_replace( 'http://', 'https://', $link );

            // do we need to perform a live test?
            // $url_parts = parse_url( $link );
            // $domain = $url_parts['host'];
            // if( !in_array( $domain, self::$bypass ) ) {

                // check if link is live 
                $response = wp_remote_get( $link );
                // if( wp_remote_retrieve_response_code( $response ) !== 200 ) 
                //     throw new Exception( 'Link is not live' );
                
                if( wp_remote_retrieve_response_code( $response ) === 200 ) {

                    // try to get title
                    $body = wp_remote_retrieve_body( $response );
                    if( 
                        !empty( $start = strpos( $body, '<title>' ) ) &&
                        !empty( $end = strpos( $body, '</title>' ) )
                    ) {
                        $start = $start + 7; // strlen( '<title>' )
                        $title = substr( $body, $start, ( $end - $start ) );
                        $title = wp_strip_all_tags( $title );
                        $title = sanitize_text_field( $title );
                    }
                }
            // }

            // check if link was already added
            if( loa_check_for_already_existing_link( $link ) )
                throw new Exception( 'Link already saved' );

            // default article title
            if( !isset( $title ) || empty( $title ) )
                $title = 'Article';

            // if we're here, then link is new and ready to be added
            $tags = [];
            if( isset( $data['link_tag'] ) && !empty( $tags = $data['link_tag'] ) ) {
                $tags = array_map( 'intval', $tags );
                $tags = array_filter( array_unique( $tags ) );
            }

            // save link as new link
            $post_id = wp_insert_post(
                [
                    'post_title'    => $title,
                    'post_type'     => 'article',
                    'post_status'   => 'publish'
                ]
            );

            // set article tags
            if( !empty( $tags ) )
                wp_set_object_terms( $post_id, $tags, 'article-cat' );

            // update ACF fields
            update_field( 'article_date_added', date( 'Y-m-d', time() ), $post_id );
            update_field( 'article_url', $link, $post_id );

            // now, send article object to frontend
            $article = new Article( $post_id );
            wp_send_json_success( $article->get() );

        } catch( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        wp_die();
    }


    public function read_article()
    {
        $this->validate( $_POST );

        try {
            
            $data = [];
            if( isset( $_POST['data'] ) ) {
                $data = stripslashes( $_POST['data'] );
                $data = json_decode( $data, true );
            }

            // check for article ID
            if( !isset( $data['id'] ) || empty( $data['id'] ) ) {
                throw new Exception( 'No ID provided' );
            } else {
                $id = $data['id'];
                if( !is_numeric( $id ) ) {
                    throw new Exception( 'ID needs to be an integer' );
                } else {
                    $id = intval( $id );
                }
            }

            // find article
            $article = get_post( $id );
            if( empty( $article ) )
                throw new Exception( 'No article exists by that ID' );

            // update article's status
            $update = update_field( 'article_date_read', date( 'Y-m-d', time() ), $id );
            if( !$update ) {
                throw new Exception( 'Failed to update article' );
            } else {
                wp_send_json_success( $id );
            }

        } catch( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        wp_die();
    }


    public function get_everything()
    {
        $this->validate( $_POST );

        $return = [
            'articles'  => $this->get_articles( false ),
            'tags'      => $this->get_tags( false ),
        ];

        // get count of read articles
        $articles = get_posts(
            [
                'post_type'         => 'article',
                'posts_per_page'    => -1,
                'meta_key'          => 'article_date_read',
                'meta_value'        => '',
                'meta_compare'      => '!='
            ]
        );

        $return['read'] = count( $articles );

        wp_send_json_success( $return );
    }
}


Endpoint::instance();
