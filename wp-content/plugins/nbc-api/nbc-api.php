<?php /*
	Plugin Name: NBC API
	Author: Sean Roberts
*/

function nbc_api_get_feed_data() {

	// adds posts exists admin function to plugin
	if ( ! function_exists( 'post_exists' ) ) {
    	require_once( ABSPATH . 'wp-admin/includes/post.php' );
	}

	// lets get our feed url
	$feed_url = 'https://www.nbcnewyork.com/?rss=y&most_recent=y';

	// lets move forward if the url is valid
	if($feed_url):

		// load feed xml
		$feed_data = simplexml_load_file( $feed_url );

		// convert to json
		$feed_data_json = json_encode($feed_data);

		// convert json to php array
		$feed_array = json_decode($feed_data_json,TRUE);

		// get items array from feed array
		$feed_array_items = $feed_array['channel']['item'];

		// lets grab the first 15 items from the array
		$feed_array_items_slice = array_slice($feed_array_items, 1, 15, true);

		// var dump area for troubleshooting
		echo '<pre>';
		var_dump($feed_array_items_slice);
		echo '</pre>';

		// move through our array of latest 15 feed items
		foreach($feed_array_items_slice as $item):

				// checking if a post already exists
				$found_post = post_exists( $item['title'],'','','');

					// if the post is not found
					if(!$found_post):

						// format publish date
						$date = date_create_from_format("D, M d Y g:i:s A", $item['pubDate']);

						$date_formatted = date_format( $date, 'Y-m-d H:i:s' );


						// create the post
						$post_id = wp_insert_post(
							array(
								'post_title' =>	$item['title'],
								'post_date' => $date_formatted,
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

// run the function each time page with shortcode is loaded for now
add_shortcode( 'feed', 'nbc_api_get_feed_data' );
//add_filter( 'after_setup_theme', 'nbc_api_get_feed_data' );