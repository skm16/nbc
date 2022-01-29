<?php /*
	Plugin Name: NBC API
	Author: Sean Roberts
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
								'post_content' => $item['description'],
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
//add_shortcode( 'feed', 'nbc_api_create_posts_from_xml_feed_data' );


function nbc_api_return_posts() {
	$args = array(
		'post_type' => 'post',
		'posts_per_page' => -1,
	);
	$posts = get_posts($args);
	if (empty($posts)):
    	return new WP_Error( 'no_posts_found', 'No posts found.', array('status' => 404) );
    endif;
    $items = array();
    foreach($posts as $item):
    	$items[] = array('title'=> $item->post_title, 'pub_date'=>$item->post_date);
    endforeach;
    //$posts = array('title'=> $post->post_title, 'pub_date'=>$post->post_date);
    $response = new WP_REST_Response($items);
    $response->set_status(200);
    return $response;
}

function nbc_api_add_api_route() {
	register_rest_route( 'testfeed/v1', 'ingestedstories', array(
                'methods'  => 'GET',
                'callback' => 'nbc_api_return_posts'
      ));
}
add_action('rest_api_init', 'nbc_api_add_api_route');

