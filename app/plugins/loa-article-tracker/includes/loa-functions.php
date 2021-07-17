<?php

defined( 'ABSPATH' ) || exit;


if( !function_exists( 'vd' ) ) {
    function vd( $args = [], $die = false ) {
        echo '<pre>'; 
        var_dump( $args ); 
        echo '</pre>';
        
        if( $die )
            die();
    }
}


function loa_check_for_preexisting_article( $url ) {
    $articles = get_posts(
        [
            'post_type'     => 'article',
            'meta_key'      => 'article_url',
            'meta_value'    => $url
        ]
    );

    return boolval( $articles );
}