<?php

declare( strict_types = 1 );

namespace Loa\Controller;




use DOMDocument;
use DOMXPath;
use Vril_Utility;

defined( 'ABSPATH' ) || exit;


class Helper
{
	/**
	 * Singular hashing system for codes
	 *
	 * @param	string	$code 	Code to hash
	 * @param 	bool 	$salted Wrap code to salt?
	 * @return 	string 			Hashed code
	 */
	public static function hash( string $code, bool $salted = true ): string
	{
		if( $salted ) {
			$code = sprintf( 
				'%s%s%s', 
				wp_salt(), 
				$code, 
				wp_salt( 'secure_auth' ) 
			);
		}

		return hash( VRIL_HASH_METHOD, $code );
	}


	/**
	 * Check if value matches a tag
	 *
	 * @param	int		$arg 	Potential tag ID
	 * @return 	bool 			True, if arg matches tag
	 */
	public static function is_tag_id( int $arg ): bool
	{
		if( !empty( $arg ) ) {
			$match = get_term( $arg, Loa()->core::TAXONOMY );

			return is_a( $match, 'WP_Term' );
		}

		return false;
	}


	/**
	 * Fetch article meta
	 * 
	 * Right now, this includes title and description, but could include more items in future.
	 *
	 * @param	string 	$url 	URL
	 * @return	array 			Metadata
	 */
	public static function fetch_meta_for_url( string $url ): array
	{
		$meta = [
			'title'			=> '',
			'description'	=> '',
			'canonical'		=> '',
		];

		if( !empty( $url = esc_url( $url ) ) ) {

			$request = wp_safe_remote_get( $url );

			if( 200 === wp_remote_retrieve_response_code( $request ) ) {
				$html = wp_remote_retrieve_body( $request );

				libxml_use_internal_errors( true );
				$doc = new DOMDocument( $html );
				$doc->loadHTML( $html );

				$xpath = new DOMXPath( $doc );

				$nodes = $xpath->query( '//head/title' );

				foreach( $nodes as $node ) {
					$text = trim( $node->nodeValue );
					$text = self::sanitize_var_heavy( $text );

					if( !empty( $text ) ) {
						$meta['title'] = $text;
						break;
					}
				}

				$nodes = $xpath->query( '//head/meta' );
				foreach( $nodes as $node ) {
					if( 'description' === $node->getAttribute('name') ) {
						$text = trim( $node->getAttribute('content') );
						$text = self::sanitize_var_heavy( $text );

						if( !empty( $text ) ) {
							$meta['description'] = $text;
							break;
						}
					}
				}

				unset( $doc );
				unset( $xpath );
			}
		}

		return $meta;
	}


	/**
	 * Sanitizes strings and converts any special characters
	 *
	 * @param	string	$var 	Value to sanitize
	 * @return 	string 			Sanitized value
	 */
	private static function sanitize_var_heavy( string $value ): string
	{
		$value = Vril_Utility::sanitize_var( $value );
		$value = filter_var( $value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );

		return $value;
	}

}
