<?php
/*
Plugin Name: Business Reviews
Plugin URI: http://knockoutwebsolutions.com/business-reviews
Description: Fetch your business reviews from multiple websites i.e. Goole, Yelp as well as provide users
a way to review in your site.
Version: 1.0
Author: Mohaiemnul Adnan, KnockoutWebSolutions
Author URI: http://knockoutwebsolutions.com
License: Personal
*/

if( $_SERVER['SCRIPT_FILENAME'] == __FILE__ ) {
  header('Location: /');
  exit;
}

require_once('lib/OAuth.php');


/**
 * Register review post type to handle website reviews.
 */
function br_register_review_post_type() {

	$labels = array(
		'name'                => _x( 'Reviews', 'Post Type General Name', 'business-review' ),
		'singular_name'       => _x( 'Review', 'Post Type Singular Name', 'business-review' ),
		'menu_name'           => __( 'Business Review', 'business-review' ),
		'parent_item_colon'   => __( 'Parent Item:', 'business-review' ),
		'all_items'           => __( 'All Reviews', 'business-review' ),
		'view_item'           => __( 'View Review', 'business-review' ),
		'add_new_item'        => __( 'Add New Review', 'business-review' ),
		'add_new'             => __( 'Add New', 'business-review' ),
		'edit_item'           => __( 'Edit Review', 'business-review' ),
		'update_item'         => __( 'Update Review', 'business-review' ),
		'search_items'        => __( 'Search Reviews', 'business-review' ),
		'not_found'           => __( 'Not found', 'business-review' ),
		'not_found_in_trash'  => __( 'Not found in Trash', 'business-review' ),
	);
	$args = array(
		'label'               => __( 'business_review', 'business-review' ),
		'description'         => __( 'Review of a business', 'business-review' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'custom-fields', ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => false,
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-star-half',
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'rewrite'             => false,
		'capability_type'     => 'page',
	);
	register_post_type( 'business_review', $args );

}

add_action( 'init', 'br_register_review_post_type' );


/**
 * Returns the configuration parameter for Business Review plugin
 * 
 * @param string $key Name of the parameter
 */
function br_config( $key ){
	$setting = array(
		'YELP_CONSUMER_KEY'       => 'NgM1IEbIDTfHQGUlo0sdEw',
		'YELP_CONSUMER_SECRET'    => 'KdXqKjBzIOb62tc3mOt3HrviCvg',
		'YELP_TOKEN'              => 'VryQxQd4XEW8IznSOlf71Rm706KpDmCU',
		'YELP_TOKEN_SECRET'       => '9x-A_74rS6rzeBoP1ZKo6pOWjsY',
		'YELP_API_HOST'           => 'api.yelp.com',
		'YELP_BUSINESS_PATH'      => '/v2/business/',
		'YELP_BUSINESS_ID'        => 'advanced-vision-care-jupiter',
		'GOOGLE_BUSINESS_ID'      => 'ChIJfztGyJbV3ogR1syto6WRKfY' );

	return isset($setting[$key]) ? $setting[$key] : false;
}



/**
 * Specify wheather it is the review page or not.
 */
function br_is_review_page(){
	return true;
}


/**
 * Add scripts on the front-end
 */
function br_enqueue_front_end(){

	if(!br_is_review_page()) return;

	wp_enqueue_script( 
		'google-place', 
		'https://maps.googleapis.com/maps/api/js?libraries=places', array() );
	
	wp_enqueue_script( 
		'angular', 
		plugins_url( 'js/angular.min.js', __FILE__ ), 
		array(), 
		'1.3.9' );
	
	wp_enqueue_script( 
		'angularjs-google-places', 
		plugins_url( 'js/angularjs-google-places.js', __FILE__ ), 
		array('jquery', 'angular', 'google-place') );
	
	wp_enqueue_script( 
		'fancybox', 
		plugins_url( 'js/jquery.fancybox.pack.js', __FILE__ ), 
		array('jquery') );
	
	wp_enqueue_script( 
		'google-place-review-module', 
		plugins_url( 'js/businessReviewModule.js', __FILE__ ), 
		array('angularjs-google-places', 'jquery', 'angular') );
	wp_localize_script( 'google-place-review-module', 'br', array(
		'yelpReviewURL'    => admin_url('admin-ajax.php?action=yelp-business'),
		'googleBusinessID' => br_config('GOOGLE_BUSINESS_ID') ) );



	wp_enqueue_style( 'fancybox', plugins_url( 'css/jquery.fancybox.css', __FILE__ ) );
}
add_action('wp_enqueue_scripts', 'br_enqueue_front_end');


function br_review_shortcode_content(){
	ob_start();
	?>
	<button id="submit-review" data-fancybox-type="iframe">Submit your review</button>

	<div ng-app="businessReviews">
		<div ng-controller="GoogleReviewsCtrl">
			<div class="review" ng-repeat="review in reviews | orderBy: 'timestamp'">
				<h2>{{review.author}} - {{review.rating}} - {{review.timestamp}} - {{review.source}}</h2>
				<p>{{review.comment}}</p>
			</div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($){
		$('#submit-review').fancybox({
			href: 'http://mohaimenuls-imac.local:5757/'
		});
	})
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode('business_reviews', 'br_review_shortcode_content');


/** 
 * Makes a request to the Yelp API and returns the response
 * 
 * @param    $host    The domain host of the API 
 * @param    $path    The path of the APi after the domain
 * @return   The JSON response from the request      
 */
function br_yelp_request($host, $path) {
    $unsigned_url = "http://" . $host . $path;

    // Token object built using the OAuth library
    $token = new OAuthToken(br_config('YELP_TOKEN'), br_config('YELP_TOKEN_SECRET'));

    // Consumer object built using the OAuth library
    $consumer = new OAuthConsumer(br_config('YELP_CONSUMER_KEY'), br_config('YELP_CONSUMER_SECRET'));

    // Yelp uses HMAC SHA1 encoding
    $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

    $oauthrequest = OAuthRequest::from_consumer_and_token(
        $consumer, 
        $token, 
        'GET', 
        $unsigned_url
    );
    
    // Sign the request
    $oauthrequest->sign_request($signature_method, $consumer, $token);
    
    // Get the signed URL
    $signed_url = $oauthrequest->to_url();
    
    // Send Yelp API Call
    $ch = curl_init($signed_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    
    return $data;
}


/**
 * Query the Business API by business_id
 * 
 * @param    $business_id    The ID of the business to query
 * @return   The JSON response from the request 
 */
function br_get_yelp_business($business_id) {
    $business_path = br_config('YELP_BUSINESS_PATH') . $business_id;
    
    return br_yelp_request(br_config('YELP_API_HOST'), $business_path);
}

/**
 * Displays the business json object to use from admin-ajax.php
 * 
 * The admin-ajax.php will be embaded as js file 
 */
function br_yelp_business_object(){
	// header("Content-type: text/javascript");
	echo br_get_yelp_business(br_config('YELP_BUSINESS_ID'));
	exit;
}
add_action('wp_ajax_yelp-business', 'br_yelp_business_object');
add_action('wp_ajax_nopriv_yelp-business', 'br_yelp_business_object');
