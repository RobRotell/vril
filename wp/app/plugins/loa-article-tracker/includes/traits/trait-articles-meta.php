<?php


namespace Loa\Traits;


defined( 'ABSPATH' ) || exit;


trait Articles_Meta
{
	/**
	 * Get timestamp for when articles were last updated
	 * 
	 * Includes both adding new article or updating article state like "read" or "favorite"
	 *
	 * @return 	int 	Timestamp
	 */
	public static function get_last_updated(): int
	{
		return Loa()->last_updated->get_timestamp();
	}

	
	/**
	 * Get count of all articles, regardless of read status
	 *
	 * @return 	int 	Count of all articles
	 */
	public static function get_article_count(): int
	{
		return Loa()->helper::get_total_article_count();
	}

	
	/**
	 * Get count of unread articles
	 *
	 * @return 	int 	Count of unread articles
	 */
	public static function get_article_count_unread(): int
	{
		return Loa()->helper::get_unread_articles( true );
	}

	
	/**
	 * Get count of read articles
	 *
	 * @return 	int 	Count of read articles
	 */
	public static function get_article_count_read(): int
	{
		return Loa()->helper::get_read_articles( true );
	}

}
