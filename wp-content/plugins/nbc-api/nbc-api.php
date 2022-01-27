<?php /*
	Plugin Name: NBC API
	Author: Sean Roberts
*/

function nbc_api_get_feed_data() {

	if ( ! function_exists( 'post_exists' ) ) {
    	require_once( ABSPATH . 'wp-admin/includes/post.php' );
	}

	$feed_url = 'https://www.nbcnewyork.com/?rss=y&most_recent=y';

	if($feed_url):

		$feed_data = simplexml_load_file( $feed_url );

		$feed_data_json = json_encode($feed_data);

		$feed_array = json_decode($feed_data_json,TRUE);

		$feed_array_items = $feed_array['channel']['item'];

		$feed_array_items_slice = array_slice($feed_array_items, 1, 15, true);

		echo '<pre>';
		var_dump($feed_array_items_slice);
		echo '</pre>';

		foreach($feed_array_items_slice as $item):

				$found_post = post_exists( $item['title'],'','','');

					if(!$found_post):

					$post_id = wp_insert_post(
						array(
							'post_title' =>	$item['title'],
							//'post_date' => $item['pubDate'],
							//'post_content' => $item['description'],
							'post_status' =>	'publish',
							'post_type' =>	'post'
						)
					);

				endif;

		endforeach;

	endif;

	return false;
}

add_shortcode( 'feed', 'nbc_api_get_feed_data' );
//add_filter( 'after_setup_theme', 'nbc_api_get_feed_data' );