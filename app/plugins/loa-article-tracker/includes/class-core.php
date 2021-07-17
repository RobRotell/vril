<?php

namespace Loa_Article_Tracker;

use DOMDocument;
use DOMXPath;

defined( 'ABSPATH' ) || exit;


class Core
{
    const POSTTYPE = 'article';
    const TAXONOMY = 'article-cat';


	public function __construct()
	{
		$this->add_wp_hooks();
	}


	private function add_wp_hooks()
	{
        add_action( 'init', [ $this, 'add_post_type' ] );
        add_action( 'init', [ $this, 'add_taxonomy' ] );		
	}

	
    public function add_post_type()
    {
        $labels = [
            'name'                      => 'Articles',
            'singular_name'             => 'Article',
            'add_new_item'              => 'Add New Article',
            'edit_item'                 => 'Edit Article',
            'new_item'                  => 'New Article',
            'view_item'                 => 'View Article',
            'view_items'                => 'View Articles',
            'search_items'              => 'Search Articles',
            'not_found'                 => 'No Articles found',
            'not_found_in_trash'        => 'No Articles found in Trash',
            'all_items'                 => 'All Articles',
            'archives'                  => 'Article Archives',
            'attributes'                => 'Article Attributes',
            'insert_into_item'          => 'Insert into Article',
            'uploaded_to_this_item'     => 'Uploaded to this Article',
            'item_published'            => 'Article published',
            'item_published_privately'  => 'Article published privately',
            'item_reverted_to_draft'    => 'Article reverted to draft',
            'item_scheduled'            => 'Article scheduled',
            'item_updated'              => 'Article updated'            
        ];

        $args = [
            'description'           => 'Articles for Rob to read (or have read)',
            'labels'                => $labels,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_icon'             => 'dashicons-admin-links',
            'supports'              => [ 'title', 'editor' ],
            'taxonomies'            => [ self::TAXONOMY ],
            'show_in_rest'          => true,
        ];

        register_post_type( self::POSTTYPE, $args );
    }


    public function add_taxonomy()
    {
        $labels = [
            'name'          => 'Categories',
            'singular_name' => 'Category',
        ];
        
        $args = [
            'label'         => 'Categories',
            'labels'        => $labels,
            'show_tagcloud' => false
        ];

        register_taxonomy( self::TAXONOMY, self::POSTTYPE, $args );
    }
}



// \WP_CLI::add_command( 'init', function() {
//     $for_json = [];

//     $articles = get_posts(
//         [
//             'post_type' => 'article',
//             'posts_per_page' => -1,
//         ]
//     );

//     foreach( $articles as $article ) {
//         $post_id = $article->ID;

//         $obj = [
//             'url' => get_field( 'article_url', $article->ID ),
//             'title' => '',
//             'description' => '',
//             'read' => get_field( 'article_is_read', $article->ID ),
//             'favorite' => get_field( 'article_is_favorite', $article->ID ),
//             'terms' => []
//         ];

//         $url = get_field( 'article_url', $article->ID );
//         $request = wp_remote_get( $url );

//         if( 200 !== wp_remote_retrieve_response_code( $request ) ) {
//             continue;
//         }
//         $html = wp_remote_retrieve_body( $request );

//         $doc = new DOMDocument();
//         $doc->loadHTML( $html );

//         $xpath = new DOMXPath( $doc );
//         $nodes = $xpath->query( '//head/title' );

//         foreach( $nodes as $node ) {
//             $text = trim( $node->nodeValue );
//             $text = filter_var( $text, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );

//             // $text = sanitize_text_field( $text );

//             if( !empty( $text ) ) {
//                 $obj['title'] = $text;
//                 break;
//             }
//         }

//         $nodes = $xpath->query( '//head/meta' );
//         foreach( $nodes as $node ) {
//             if( 'description' === $node->getAttribute('name') ) {
//                 $text = trim( $node->getAttribute( 'content' ) );
//                 $text = sanitize_text_field( $text );

//                 if( !empty( $text ) ) {
//                     $obj['description'] = $text;
//                     break;
//                 }
//             }
//         }

//         $terms = wp_get_object_terms( $post_id, 'article-cat' );
//         foreach( $terms as $term ) {
//             $obj['terms'][] = $term->name;
//         }

//         unset( $doc );
//         unset( $xpath );

//         $for_json[] = $obj;
//     }

//     $for_json = json_encode( $for_json );
//     file_put_contents( __DIR__ . '/log', $for_json );
//     echo 'done';
// });