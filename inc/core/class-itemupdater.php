<?php
/**
 * This class handles the updating of items from feeds
 *
 * @package Lilina
 */

/**
 * This class handles the updating of items from feeds.
 *
 * Contains both command line and browser interfaces.
 * @package Lilina
 */
class ItemUpdater {
	protected static $feeds = array();
	public static function set_feeds($feeds) {
		self::$feeds = $feeds;
	}

	/**
	 * Process through the feeds and add the new items to the database
	 */
	public static function process() {
		require_once(LILINA_INCPATH . '/contrib/simplepie/simplepie.inc');
		$updated = false;
		$return = array();
		
		foreach(self::$feeds as $feed) {
			do_action('iu-feed-start', $feed);
			$sp = self::load_feed($feed);
			if($error = $sp->error()) {
				throw new Exception(sprintf(_r('An error occurred with "%2$s": %1$s'), $error, $feed['name']), Errors::get_code('api.itemupdater.itemerror'));
			}
			
			$count = 0;
			$items = $sp->get_items();
			foreach($items as $item) {
				$new_item = self::normalise($item, $feed['id']);
				$new_item = apply_filters('item_data_precache', $new_item);
				if(ItemCache::get_instance()->check_item($new_item)) {
					$count++;
					$updated = true;
				}
			}
			do_action('iu-feed-finish', $feed);
			$return[$feed['id']] = $count;
		}

		ItemCache::get_instance()->sort_all();
		
		if($updated)
			ItemCache::get_instance()->save_cache();
		
		return $return;
	}

	/**
	 * Load and process a feed using SimplePie
	 *
	 * @param string $feed Feed detail array, as returned by Feeds::get()
	 * @return SimplePie
	 */
	public static function load_feed($feed) {
		// This loads the useragent
		class_exists('HTTPRequest');
		global $lilina;

		$sp = new SimplePie();
		$sp->set_useragent(LILINA_USERAGENT . ' SimplePie/' . SIMPLEPIE_BUILD);
		$sp->set_stupidly_fast(true);
		$sp->set_cache_location(get_option('cachedir'));
		//$sp->set_cache_duration(0);
		$sp->set_favicon_handler(get_option('baseurl') . 'lilina-favicon.php');
		$sp = apply_filters('simplepie-config', $sp);

		$sp->set_feed_url($feed['feed']);
		$sp->init();

		/** We need this so we have something to work with. */
		$sp->get_items();

		if(!isset($sp->data['ordered_items'])) {
			$sp->data['ordered_items'] = $sp->data['items'];
		}

		/** Let's force sorting */
		usort($sp->data['ordered_items'], array(&$sp, 'sort_items'));
		usort($sp->data['items'], array(&$sp, 'sort_items'));

		return $sp;
	}

	/**
	 * Normalise a SimplePie_Item into a stdClass
	 *
	 * Converts a SimplePie_Item into a new-style stdClass
	 */
	public function normalise($item, $feed = '') {
		if($enclosure = $item->get_enclosure()) {
			$enclosure = $enclosure->get_link();
		}
		else {
			// SimplePie_Item::get_enclosure() returns null, so we need to change this to false
			$enclosure = false;
		}
		if($author = $item->get_author()) {
			$author = array(
				'name' => $item->get_author()->get_name(),
				'url' => $item->get_author()->get_link()
			);
		}
		else {
			$author = array(
				'name' => false,
				'url' => false
			);
		}
		$new_item = (object) array(
			'hash'      => $item->get_id(true),
			'timestamp' => $item->get_date('U'),
			'title'     => $item->get_title(),
			'content'   => $item->get_content(),
			'summary'   => $item->get_description(),
			'permalink' => $item->get_permalink(),
			'metadata'  => (object) array(
				'enclosure' => $enclosure
			),
			'author'    => (object) $author,
			'feed'      => $item->get_feed()->get_link()
		);
		if(!empty($feed))
			$new_item->feed_id = $feed;
		return apply_filters('item_data', $new_item);
	}

	public static function log($type, $detail) {}
}