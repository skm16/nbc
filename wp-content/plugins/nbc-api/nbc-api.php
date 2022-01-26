<?php /*
	Plugin Name: NBC API
	Author: Sean Roberts
*/

function nbc_api_get_feed_data() {
	$feed_url = 'https://www.nbcnewyork.com/?rss=y&most_recent=y';
	if($feed_url){
		$feed_data = wp_remote_get( $feed_url );
		return $feed_data;
	}
	return false;
}
