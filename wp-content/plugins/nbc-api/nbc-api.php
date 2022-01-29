<?php /*
	Plugin Name: NBC API
	Author: Sean Roberts
	Description: Pulls xml feed from nbc endpoint, adds items as posts and creates a new rest api endpoint for posts.
	Version: 0.1
*/

function nbc_api_create_posts_from_xml_feed_data() {

	// adds posts exists admin function to plugin
	if ( ! function_exists( 'post_exists' ) ) {
    	require_once( ABSPATH . 'wp-admin/includes/post.php' );
	}

	// lets get our feed url
	$feed_url = 'https://www.nbcnewyork.com/?rss=y&most_recent=y';

	// lets move forward if the url is valid
	if($feed_url):

		// load feed xml
		$feed_data = simplexml_load_file( $feed_url, 'SimpleXMLElement', LIBXML_NOCDATA );

		// convert to json
		$feed_data_json = json_encode($feed_data);

		// convert json to php array
		$feed_array = json_decode($feed_data_json,TRUE);

		// get items array from feed array
		$feed_array_items = $feed_array['channel']['item'];

		// lets grab the first 15 items from the array
		$feed_array_items_slice = array_slice($feed_array_items, 0, 15, true);

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
								'post_content' => $item['description'],
								'post_status' => 'publish',
								'post_type' => 'post'
							)
						);

				endif;

		endforeach;

	endif;

	return false;
}

// callback function for api endpoint
function nbc_api_return_posts() {
	$args = array(
		'post_type' => 'post',
		'posts_per_page' => -1,
	);
	$posts = get_posts($args);
	// if no posts are returned show error message
	if (empty($posts)):
    	return new WP_Error( 'no_posts_found', 'No posts found.', array('status' => 404) );
    endif;
    // if posts are found
    $items = array();
    // pass desired post data into an array
    foreach($posts as $item):
    	$items[] = array('title'=> $item->post_title, 'pub_date'=>$item->post_date);
    endforeach;
    // return array in the rest response
    $response = new WP_REST_Response($items);
    $response->set_status(200);
    return $response;
}

// register api endpoint
function nbc_api_add_api_route() {
	register_rest_route( 'testfeed/v1', 'ingestedstories', array(
                'methods'  => 'GET',
                'callback' => 'nbc_api_return_posts'
      ));
}
add_action('rest_api_init', 'nbc_api_add_api_route');

// add every 10 mins 
function nbc_api_add_every_ten_minutes( $schedules ) {
    $schedules['every_ten_minutes'] = array(
            'interval'  => 60 * 10,
            'display'   => __( 'Every 10 Minutes', 'nbc-feed-cron-schedule' )
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'nbc_api_add_every_ten_minutes' );

// schedule feed action if it's not already scheduled
if ( ! wp_next_scheduled( 'nbc_api_add_every_ten_minutes' ) ) {
    wp_schedule_event( time(), 'every_ten_minutes', 'nbc_api_add_every_ten_minutes' );
}

// hook into that action that'll fire every ten minutes
add_action( 'nbc_api_add_every_ten_minutes', 'nbc_api_create_posts_from_xml_feed_data' );

// deactive feed import on plugin deativation
function nbc_api_deactivate() {
    wp_clear_scheduled_hook( 'nbc_api_add_every_ten_minutes' );
}
 
add_action('init', function() {
    register_deactivation_hook( __FILE__, 'nbc_api_deactivate' );
});