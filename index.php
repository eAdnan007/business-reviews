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
	$setting = array();

	return isset($setting[$key]) ? $setting[$key] : false;
}

/**
 * Add scripts on the front-end
 */
function br_enqueue_front_end(){

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 
		'fancybox', 
		plugins_url( 'js/jquery.fancybox.pack.js', __FILE__ ), 
		array('jquery') );

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
	});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode('business_reviews', 'br_review_shortcode_content');
