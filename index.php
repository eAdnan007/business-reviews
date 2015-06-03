<?php
/*
Plugin Name: Business Reviews
Plugin URI: http://knockoutwebsolutions.com/business-reviews
Description: Let visitors of your website review your business and show off the reviews at the same time.
Version: 1.5
Author: Knockout Web Solutions
Author URI: http://knockoutwebsolutions.com
License: Personal
*/

/**
 * Check if the script has been tried to access directly.
 */
if( $_SERVER['SCRIPT_FILENAME'] == __FILE__ ) {
  header('Location: /');
  exit;
}

/**
 * If plugin is already installed(may be by a different folder name), do not rediclare.
 */
if( !class_exists( 'Business_Review' ) ):
/**
 * The plugin container.
 */
class Business_Review {

	private $review_info;

	public function __construct(){

		add_action( 'redux/extensions/business_review_config/before', array( $this, 'redux_register_custom_extension_loader' ), 0 );
		add_filter( 'manage_business_review_posts_columns', array( $this, 'replace_business_review_columns' ) );


		add_action( 'init', array( $this, 'load_text_domain' ) );
		add_action( 'init', array( $this, 'register_review_post_type' ) );
		add_action( 'init', array( $this, 'update_settings' ) );
		add_action( 'add_meta_boxes_business_review', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post', array( $this, 'save_business_review_info_meta' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'business_review_column_info' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_end' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_back_end' ) );


		// Ajax actions
		add_action( 'wp_ajax_review_form', array( $this, 'review_form' ) );
		add_action( 'wp_ajax_nopriv_review_form', array( $this, 'review_form' ) );
		add_action( 'wp_ajax_review_submit', array( $this, 'review_submit' ) );
		add_action( 'wp_ajax_nopriv_review_submit', array( $this, 'review_submit' ) );

		add_shortcode( 'business_reviews', array( $this, 'review_shortcode_content' ) );
		add_shortcode( 'location_rating', array( $this, 'location_rating' ) );
		add_shortcode( 'br_field', array( $this, 'review_field' ) );


		// Create the settings page.
		if ( !class_exists( 'ReduxFramework' ) && file_exists( dirname( __FILE__ ) . '/options/ReduxCore/framework.php' ) ) {
		    require_once( dirname( __FILE__ ) . '/options/ReduxCore/framework.php' );
		}
		if ( !isset( $redux_demo ) && file_exists( dirname( __FILE__ ) . '/br-config.php' ) ) {
		    require_once( dirname( __FILE__ ) . '/br-config.php' );
		}
	}

	/**
	 * Loads textdomain to make the plugin translation ready.
	 */
	public function load_text_domain() {
		load_plugin_textdomain('business-review', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register review post type to handle website reviews.
	 */
	public function register_review_post_type() {

		$labels = array(
			'name'                => _x( 'Reviews', 'Post Type General Name', 'business-review' ),
			'singular_name'       => _x( 'Review', 'Post Type Singular Name', 'business-review' ),
			'menu_name'           => __( 'Business Reviews', 'business-review' ),
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
			'label'               => __( 'Business Review', 'business-review' ),
			'description'         => __( 'Review of a business', 'business-review' ),
			'labels'              => $labels,
			'supports'            => array( 'editor', 'custom-fields', ),
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


	/**
	 * Get latest configuration to this object on init.
	 */
	public function update_settings(){

		$this->review_info = array(
			'reviewer_ip'             => array(
				'title'                => __( 'Reviewer IP', 'business-review' ),
				'type'                 => 'hidden' ),
			'location'               => array(
				'title'                => __( 'Office Location', 'business-review' ),
				'type'                 => 'select',
				'options'              => $this->config( 'locations' ) ),
			'first_name'             => array(
				'title'                => __( 'First Name', 'business-review' ),
				'type'                 => 'text' ),
			'last_name'              => array(
				'title'                => __( 'Last Name', 'business-review' ),
				'type'                 => 'text' ),
			'email'                  => array(
				'title'                => __( 'Email', 'business-review' ),
				'type'                 => 'text',
				'validation'           => 'email' ),
			'phone'                  => array(
				'title'                => __( 'Phone Number', 'business-review' ),
				'type'                 => 'text',
				'validation'           => 'tel' ),
			'rating_avg'             => array(
				'title'                => __( 'Average Rating', 'business-review' ),
				'type'                 => 'text' )
		);
		
		$dynamic_fields = $this->config( 'rating_fields' );
		
		foreach( $dynamic_fields as $i => $df ){
			$field = array(
				'title'        => $df['question'],
				'short_title'  => $df['name']
			);
			
			if( 'rating' == $df['field_type'] ){
				
				$sd = array(
					'type'         => 'rating',
					'weight'       => $df['rating_weight'] );
			}
			elseif( 'question' == $df['field_type'] ){
				$sd['type'] = $df['question_type'];
			}
			
			$this->review_info["field_$i"] = array_merge( $field, $sd );
		}
	}


	/**
	 * Add metabox in business review post type
	 */
	public function add_metaboxes(){
		add_meta_box(
			'namediv',
			__( 'Review Information', 'business-review' ),
			array( $this, 'review_info_metabox_content' ),
			'business_review' );
	}


	/**
	 * Provides all the information related with a review.
	 *
	 * @param int $review_id ID of the review post.
	 */
	public function get_review_info( $review_id = null ){
		if( null == $review_id ) {
			global $post;
			$review_id = $post->ID;
		}

		$review_info = $this->review_info;

		foreach( $review_info as $key => $info ){
			$review_info[$key]['value'] = get_post_meta( $review_id, 'br_review_info_'.$key, true );
		}

		$locations = $this->config('locations');
		$review_info['location']['value'] = array(
			'slug'  => $review_info['location']['value'],
			'title' => $locations[$review_info['location']['value']] );

		return $review_info;
	}


	/**
	 * Displays the metabox content for Author metabox in business_review post type.
	 */
	public function review_info_metabox_content(){
		$review_info = $this->get_review_info();
		?>
		<table class="form-table editcomment">
		<tbody>
			<?php foreach( $review_info as $key => $field ): ?>
				<tr valign="top">
					<td class="first"><?php echo isset($field['short_title']) ? $field['short_title'] : $field['title']; ?></td>
					<?php
					$data = array(
						'name'  => "review_info[$key]",
						'id'    => "review-info-$key",
						'value' => $field['value'] );

					if( $key == 'location' ) $data['value'] = $data['value']['slug'];

					if( isset( $field['options'] ) ) $data['options'] = $field['options'];


					$field_type = $field['type'];
					if( 'hidden' == $field_type ){
						$field_type = 'text';
					}
					if( 'rating' == $field_type ){
						$field_type = 'select';
						$data['options'] = array( __( 'Select', 'business-review' ), 1, 2, 3, 4, 5 );
					}
					if( 'select' == $field_type ) $data['atts'] = array( 'style' => 'width:98%;' );
					?>
					<td><?php $this->create_field( $field_type, $data ); ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
		</table>
		<?php wp_nonce_field( 'review_submit', 'review_info[nonce]', false, true ); ?>
		<?php
	}


	/**
	 * Saves the review post author meta on save of business_review
	 */
	public function save_business_review_info_meta( $post_id, $post ){
		if ( wp_is_post_revision( $post_id ) ) return;


		if( !isset($_POST['review_info']['nonce']) || !wp_verify_nonce( $_POST['review_info']['nonce'], 'review_submit' ) ) return;

		// No need to proceed in case of other post type.
		if( 'business_review' != $post->post_type ) return;

		/**
		 * Store the ip address of reviewer
		 *
		 * The review might be updated by some website users after the review has been submitted for first time.
		 * In such case there will be already an ip address set. We do not want to override that value to preserve
		 * the ip address of the actual reviewer, which is ensured by the fourth argument.
		 */
		add_post_meta( $post_id, 'br_review_info_reviewer_ip', $_SERVER['REMOTE_ADDR'], true );


		$fields = $this->get_review_info( $post_id );

		$total_stars = $achived_stars = 0; // Out of how many stars are we dividing the avg
		foreach( $fields as $key => $info ){
			if( isset( $_POST['review_info'][$key] ) ) {
				update_post_meta( $post_id, 'br_review_info_'.$key, $_POST['review_info'][$key] );

				/**
				 * We have additional calculations for rating fields to calculate the average rating.
				 */
				if( 'rating' == $info['type'] ){
					if( $_POST['review_info'][$key] >= 1 && $_POST['review_info'][$key] <= 5 ){
						$total_stars   += $info['weight']; // Rating each criteria increases stars of it's weight in total
						$achived_stars += $_POST['review_info'][$key];
					}
				}
			}
		}
		if( $total_stars != 0 ){
			$avg_rating = 5.0 * ($achived_stars / $total_stars); // Avg is 5 based
			update_post_meta( $post_id, 'br_review_info_rating_avg', $avg_rating );
		}


		if(
			!current_user_can( 'publish_pages' ) // Not an authentic user to approve reviews
			&& $this->is_poor_rating( $post_id, $avg_rating ) // A poor rating
			&& $this->config( 'verify_poor_rating' ) // Review of poor rating is on
		){

			// We are just going to update the post and don't want fall in loop
			remove_action('save_post', array( $this, 'save_business_review_info_meta' ) );

			// Keep the review pending for verification
			wp_update_post( array(
				'ID'          => $post_id,
				'post_status' => 'pending' ) );

			// Done with the update. Rehooking in case if it is needed for another post(I don't think so).
			add_action( 'save_post', array( $this, 'save_business_review_info_meta' ) );
		}
	}


	/**
	 * Replace the columns in all posts screen of business_review
	 *
	 * @param mixed[] $columns Array of available columns
	 */
	public function replace_business_review_columns( $columns ){

		$columns = array(
			'cb'            => '<input type="checkbox">',
			'review_author' => __( 'Author' ),
			'location'      => __( 'Office Location', 'business-review' ),
			'text'          => __( 'Review', 'business-review' ),
			'date'          => __( 'Date' ) );

		return $columns;
	}


	/**
	 * Provides the information to put in columns in the all posts screen of business_review
	 */
	public function business_review_column_info( $column, $post_id ){
		$review = get_post( $post_id );
		$review_info = $this->get_review_info( $post_id );

		switch ( $column ) {
			case 'review_author':
				?>

				<strong>
					<?php echo get_avatar( $review_info['email']['value'], 32 ); ?>
					<?php echo $review_info['first_name']['value'] . ' ' . $review_info['last_name']['value']; ?>
				</strong>
				<br>
				<a href="mailto:<?php echo $review_info['email']['value']; ?>"><?php echo $review_info['email']['value']; ?></a>
				<div class="row-actions">
					<?php if ( !isset($_GET['post_status']) || in_array( $_GET['post_status'], array( 'publish', 'draft', 'pending' ) ) ): ?>
						<span class="edit"><a href="<?php echo get_edit_post_link( $post_id ); ?>" title="Edit this item"><?php _e('Edit');?></a> | </span>
						<span class="inline hide-if-no-js"><a href="#" class="editinline" title="Edit this item inline"><?php _e('Quick&nbsp;Edit');?></a> | </span>
						<span class="trash"><a class="submitdelete" title="Move this item to the Trash" href="<?php echo get_delete_post_link( $post_id ); ?>"><?php _e('Trash');?></a></span>
					<?php elseif( 'trash' == $_GET['post_status'] ) : ?>
						<span class="untrash"><a title="Restore this item from the Trash" href="<?php echo $this->get_undelete_post_link( $post_id ); ?>"><?php _e('Restore');?></a> | </span>
						<span class="delete"><a class="submitdelete" title="Delete this item permanently" href="<?php echo get_delete_post_link( $post_id, null, true ); ?>"><?php _e('Delete Permanently');?></a></span>
					<?php endif ?>
				</div>
				<?php
				break;
			case 'location':
				echo $this->get_review_info()['location']['value']['title'];
				break;
			case 'text':
				?>
				<div class="business_review_stars" data-rating="<?php echo $review_info['rating']; ?>" data-readonly="true"></div>
				<?php $this->show_stars('small', $review_info['rating_avg']['value']);?>
				<?php echo wp_trim_excerpt($review->post_excerpt);?>
				<?php
				break;
		}
	}

	/**
	 * Load redux extensions
	 */
	function redux_register_custom_extension_loader($ReduxFramework) {
		$path    = dirname( __FILE__ ) . '/redux_extensions/';
			$folders = scandir( $path, 1 );
			foreach ( $folders as $folder ) {
				if ( $folder === '.' or $folder === '..' or ! is_dir( $path . $folder ) ) {
					continue;
				}
				$extension_class = 'ReduxFramework_Extension_' . $folder;
				if ( ! class_exists( $extension_class ) ) {
					// In case you wanted override your override, hah.
					$class_file = $path . $folder . '/extension_' . $folder . '.php';
					$class_file = apply_filters( 'redux/extension/' . $ReduxFramework->args['opt_name'] . '/' . $folder, $class_file );
					if ( $class_file ) {
						require_once( $class_file );
					}
				}
				if ( ! isset( $ReduxFramework->extensions[ $folder ] ) ) {
					$ReduxFramework->extensions[ $folder ] = new $extension_class( $ReduxFramework );
				}
			}
	}


	/**
	 * Returns the configuration parameter for Business Review plugin
	 *
	 * @param string $key Name of the parameter
	 */
	public static function config( $key ){
		global $business_review_config;

		$locations = array();
		if( !empty( $business_review_config['locations'] ) ):
		foreach( $business_review_config['locations'] as $location ){
			$locations[sanitize_title( $location )] = $location;
		}
		endif;
		$business_review_config['locations'] = $locations;

		return isset($business_review_config[$key]) ? $business_review_config[$key] : false;
	}

	/**
	 * Add scripts on the front-end
	 */
	public function enqueue_front_end(){

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script(
			'fancybox',
			plugins_url( 'js/jquery.fancybox.pack.js', __FILE__ ),
			array('jquery') );
		wp_enqueue_script(
			'timeago',
			plugins_url( 'js/jquery.timeago.js', __FILE__ ),
			array('jquery') );
		wp_enqueue_script(
			'readmore',
			plugins_url( 'js/readmore.min.js', __FILE__ ),
			array('jquery') );
		wp_enqueue_script(
			'masonry',
			plugins_url( 'js/masonry.pkgd.min.js', __FILE__ ),
			array('jquery') );
		wp_enqueue_script(
			'inputmask',
			plugins_url( 'js/jquery.inputmask.bundle.js', __FILE__ ),
			array('jquery'), '3.1.62-6' );
		wp_enqueue_script(
			'rating',
			plugins_url( 'js/rating.js', __FILE__ ),
			array('jquery') );

		wp_enqueue_script(
			'business-reviews',
			plugins_url( 'js/businessreviews.js', __FILE__ ),
			array('jquery', 'fancybox', 'timeago', 'readmore', 'masonry', 'inputmask') );
		wp_localize_script( 'business-reviews', 'br', array(
			'ajax_url'    => admin_url( 'admin-ajax.php' ) ) );

		wp_enqueue_style( 'fancybox', plugins_url( 'css/jquery.fancybox.css', __FILE__ ) );
		wp_enqueue_style( 'business-review', plugins_url( '/css/business-review.css', __FILE__ ) );
	}


	/**
	 * Add scripts and styles on back end
	 */
	public function enqueue_back_end($hook){
		wp_enqueue_style( 'business-review', plugins_url( '/css/business-review.css', __FILE__ ) );

		wp_enqueue_script(
			'business-reviews',
			plugins_url( 'js/rating.js', __FILE__ ),
			array('jquery') );
	}

	/**
	 * Returns what should be displayed when the shortcode [business_reviews] is used.
	 */
	public function review_shortcode_content(){
		ob_start();

		/**
		 * The WordPress Query class.
		 * @link http://codex.wordpress.org/Function_Reference/WP_Query
		 *
		 */
		$args = array(

			//Type & Status Parameters
			'post_type'   => 'business_review',
			'post_status' => 'publish',

			//Order & Orderby Parameters
			'order'               => 'DESC',
			'orderby'             => 'date',

			//Pagination Parameters
			'posts_per_page'         => $this->config('number_of_reviews'),
			'nopaging'               => false,
		);

		$review_query = new WP_Query( $args );

		if( $review_query->have_posts() ) :
			$this->review_location_filter();
			echo '<div class="br-wrapper">';
			while( $review_query->have_posts() ) : $review_query->the_post();
				$info = $this->get_review_info();
				?>
				<div class="br-review br-location-<?php echo $info['location']['value']['slug']; ?>">
					<div class="br-author">
						<?php
						if( $this->config('show_gravatar') ){
							echo get_avatar( $info['email']['value'], 60, '', $info['first_name']['value'] . ' ' . $info['last_name']['value'] );
						}

						if( current_user_can('edit_pages' ) ){
							echo '<a href="'.get_edit_post_link(get_the_ID()).'">'
								. $info['first_name']['value'] . ' ' . $info['last_name']['value']
								. '</a>';
						}
						else {
							echo $info['first_name']['value'] . ' ' . $info['last_name']['value'];
						}
						?>
					</div>

					<div class="br-rating">
						<?php $this->show_stars( 'small', $info['rating_avg']['value'] ); ?>
						<abbr class="timeago" title="<?php echo $this->get_iso_8601(); ?>"><?php echo get_the_time( 'F j, Y' ); ?></abbr>
					</div>
					<div class="br-location">
						<?php _ex( 'on', 'To specify location of review.', 'business-review' ); ?>
						<?php echo $info['location']['value']['title']; ?>
					</div>
					<div class="br-comment"><?php the_content(); ?></div>
				</div>
				<?php
			endwhile;
			echo '<br class="clear">';
			echo '</div>';
		endif;
		wp_reset_query();
		?>

		<div class="review-center">
			<button id="submit-review" class="btn btn-default btn-sm" data-fancybox-type="iframe"><?php _e( 'Submit Your Review', 'business-review' ); ?></button>
		</div>
		<style>
		<?php echo $this->config('custom_css'); ?>
		.br-review {
			width: <?php echo 100/$this->config('review_columns');?>%;
		}
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns date in ISO 8601 format.
	 *
	 * The jquery timeago plugin requires date to be provided on ISO 8601 format. The default wp function get_the_time()
	 * provides wrong value for that. This method overcomes the issue.
	 */
	public function get_iso_8601() {
		$format    = 'Y-m-d G:i:s';
		$timestamp = get_the_time('U');
		$timezone  = get_option('timezone_string');
		if('' == $timezone) $timezone = 'UTC';

		$datetime = new DateTime(date_i18n($format, $timestamp), new DateTimeZone($timezone));
		return $datetime->format('c');
	}


	/**
	 * Outputs a list of location clicking which the reviews can be filtered.
	 */
	private function review_location_filter(){
		$locations = $this->config('locations');
		if( sizeof( $locations ) < 2 ) return;
		?>
		<div id="br-location-filter">
			<ul class="br-filter">
				<li><a href="#all"><?php _e( 'All', 'business-review' ); ?></a></li>
				<?php foreach( $locations as $key => $title ): ?>
					<li><a href="#<?php echo $key ?>"><?php echo $title; ?></a></li>
				<?php endforeach ?>
			</ul>
		</div>
		<br class="clear">
		<?php
	}


	/**
	 * Outputs the content of the review form.
	 */
	public function review_form(){
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<?php wp_head(); ?>
		</head>
		<body style="background: #FFF;">

		<form
			action="<?php echo admin_url('admin-ajax.php'); ?>"
			id="review-form"
			method="post">
			
			<?php echo do_shortcode( $this->config( 'review_form' ) );?>
			
			<input type="hidden" name="action" value="review_submit">
			<?php wp_nonce_field( 'review_submit', 'review_info[nonce]', false, true ); ?>
			<br class="clear">
			<div class="review-center">
				<button type="submit" class="btn btn-default btn-sm"><?php _e('Submit', 'business-review'); ?></button>
			</div>
			<br class="clear">
		</form>
		<style>
		<?php echo $this->config('custom_css'); ?>
		</style>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Receives the review submission through ajax from the front-end through admin-ajax.php
	 */
	public function review_submit(){

		if( !wp_verify_nonce( $_POST['review_info']['nonce'], 'review_submit' ) ) exit;


		// Just the boolean values from configuration
		$one_review_per_ip = $this->config('one_review_per_ip');
		$one_review_per_email = $this->config('one_review_per_email');


		// Meta query for email
		$email_query = array(
			'key'     => 'br_review_info_email',
			'value'   => $_POST['review_info']['email'],
			'compare' => '='
		);
		$ip_query = array(
			'key'     => 'reviewer_ip',
			'value'   => $_SERVER['REMOTE_ADDR'],
			'compare' => '='
		);
		$location_query = array(
			'key'     => 'br_review_info_location',
			'value'   => $_POST['review_info']['location'],
			'compare' => '='
		);

		$query = array(
			'post_type'  => 'business_review'
		);


		/**
		 * Check wheather reviews are restricted per user by ip, email or both. In case they are,
		 * add the conditions to the query to fetch a list of matching reviews.
		 */
		if( $one_review_per_ip && $one_review_per_email ){
			if( $this->config('restriction_scope') == 'location' ){
				$email_query = array( 'relation' => 'AND', $email_query, $location_query );
				$ip_query = array( 'relation' => 'AND', $ip_query, $location_query );
			}

			$query['meta_query'] = array(
				'relation' => 'OR',
				$email_query,
				$ip_query
			);
		}
		else if( $one_review_per_email ){
			if( $this->config('restriction_scope') == 'location' ){
				$query['meta_query'] = array( 'relation' => 'AND', $email_query, $location_query );
			}
			else {
				$query['meta_query'] = array( $email_query );
			}
		}
		else if( $one_review_per_ip ){
			if( $this->config('restriction_scope') == 'location' ){
				$query['meta_query'] = array( 'relation' => 'AND', $ip_query, $location_query );
			}
			else {
				$query['meta_query'] = array( $ip_query );
			}
		}

		$user_could_review = true;
		if( $one_review_per_ip || $one_review_per_email ){
			$existing_reviews = get_posts( $query );
			$user_could_review = empty( $existing_reviews );
		}

		// Only create the review if the user is not caught reviewing multiple times
		if( $user_could_review ){

			/**
			 * Save the review.
			 *
			 * As the review is stored, it calls $this->save_business_review_info_meta()
			 * to save the meta associated with this review. It may also change the post_status.
			 */
			$post_id = wp_insert_post( array(
				'post_content'  => $_POST['review_info']['comment'],
				'post_type'     => 'business_review',
				'post_status'   => 'publish' ) );
		}


		?>
		<!DOCTYPE html>
		<html>
		<head>
			<?php wp_head(); ?>
		</head>
		<body class="br-review-result">
			<?php
			if( !$user_could_review ){
				echo $this->config( 'review_duplicate_error' );
			}
			elseif( $this->is_poor_rating( $post_id ) ){
				echo $this->config( 'thank_you_message_negative' );
			}
			else {
				echo $this->config( 'thank_you_message_positive' );
			}
			?>
		</body>
		</html>
		<style>
		<?php echo $this->config('custom_css'); ?>
		</style>
		<?php

		exit;
	}


	/**
	 * Conditional function to determine wheather a review is satisfied or not based on the avg.
	 *
	 * @param int $review_id ID of the review post.
	 */
	public function is_poor_rating( $review_id = null, $rating = null ){
		if( null == $review_id ){
			global $post;
			$review_id = $post->ID;
		}

		if( null == $rating ) $rating = get_post_meta( $review_id, 'br_review_info_rating_avg', true );

		return $rating < $this->config( 'satisfaction_threshold' );
	}


	/**
	 * Displays rating stars with or without the ability to rate.
	 *
	 * @param double $rating Initial rating
	 * @param string $target_field CSS selector for the hidden field which will contain the rating to send via form.
	 */
	public function show_stars( $size = '', $rating = 0.0, $target_field = null ){
		if( 'small' == $size ) $size = 'br-stars-small';
		if( 'big' == $size ) $size = 'br-stars-big';
		?>
		<div class="br-stars <?php echo $size ?>" data-value="<?php echo $rating; ?>" <?php echo null != $target_field? 'data-readonly="false" data-field="'.$target_field.'"':'data-readonly="true"'; ?>>
			<?php echo "$rating star"; ?>
		</div>
		<?php
	}


	/**
	 * Controls generation of all kind of supported fields.
	 *
	 * @param mixed[] $data Array of information regarding the field. Includes name, id, value, options and atts.
	 */
	public static function create_field( $type, $data, $echo = true ){
		
		$data = shortcode_atts(
			array(
				'id'      => '',
				'name'    => '',
				'value'   => '',
				'field'   => '',
				'size'    => '',
				'attr'    => array(),
				'options' => array()
			),
			$data
		);
		
		if( 'textarea' == $type && !isset( $data['attr']['rows'] ) ) $data['attr']['rows'] = 5;
		extract( $data );

		$attr_strs = array();
		foreach( $attr as $key => $value ){
			$attr_strs[] = "$key=\"$value\"";
		}
		$data['atts'] = implode( ' ', $attr_strs );
		
		if( !$echo ) ob_start();
		
		switch( $type ) {
			case 'email':
				unset( $data['atts'] );
				$data['attr']['class'] = implode( ' ', array( $data['attr']['class'], 'inputmask' ) );
				$data['attr']['data-inputmask'] = "'alias': 'email'";
				self::create_field( 'text', $data );
				break;
			case 'phone':
				unset( $data['atts'] );
				$data['attr']['class'] = implode( ' ', array( $data['attr']['class'], 'inputmask' ) );
				$data['attr']['data-inputmask'] = "'alias': 'mm/dd/yyyy'";
				self::create_field( 'text', $data );
				break;
			case 'phone':
				unset( $data['atts'] );
				$data['attr']['class'] = implode( ' ', array( $data['attr']['class'], 'inputmask' ) );
				$data['attr']['data-inputmask'] = "'mask': '(999) 999-9999'";
				self::create_field( 'text', $data );
				break;
			case 'text':
				self::text_field( $data );
				break;
			case 'textarea':
				self::textarea_field( $data );
				break;
			case 'select':
				self::select_field( $data );
				break;
			case 'rating':
				self::show_stars( $size, 0, "#$id" );
				self::hidden_field( $data );
				break;
		}
		
		if( !$echo ) return ob_get_clean();
	}


	/**
	 * Generates a select dropdown.
	 *
	 * @param mixed[] $data Array of information regarding the field. Includes name, id, value, options and atts.
	 */
	public static function select_field( $data ){
		extract( $data );
		?>
		<select name="<?php echo $name; ?>" id="<?php echo $id; ?>" <?php echo $atts; ?>>
			<?php foreach( $options as $k => $v ): ?>
				<option value="<?php echo $k; ?>" <?php echo $value == $k ? 'selected="selected"' : ''; ?>><?php echo $v; ?></option>
			<?php endforeach ?>
		</select>
		<?php
	}


	/**
	 * Echoes a hidden input field
	 *
	 * @param mixed[] $data Array of information regarding the field. Includes name, id, value, options and atts.
	 */
	public static function hidden_field( $data ){
		extract( $data );
		?>
		<input
			type="hidden"
			name="<?php echo $name; ?>"
			id="<?php echo $id; ?>"
			value="<?php echo $value; ?>"
			<?php echo $atts; ?>>
		<?php
	}


	/**
	 * Provides a text field.
	 *
	 * @param mixed[] $data Array of information regarding the field. Includes name, id, value, options and atts.
	 */
	public static function text_field( $data ){
		extract( $data );
		?>
		<input
			type="text"
			name="<?php echo $name; ?>"
			id="<?php echo $id; ?>"
			value="<?php echo $value; ?>"
			<?php echo $atts; ?>>
		<?php
	}


	/**
	 * Provides a textarea.
	 *
	 * @param mixed[] $data Array of information regarding the field. Includes name, id, value, options and atts.
	 */
	public static function textarea_field( $data ){
		extract( $data );
		?>
		<textarea
			name="<?php echo $name; ?>"
			id="<?php echo $id; ?>"
			<?php echo $atts; ?>
		><?php echo $value; ?></textarea>
		<?php
	}


	/**
	 * Get post restore link
	 *
	 * Produces a link that provides a nonced link which can restore a post from trash.
	 *
	 * @param int $post_id ID of the post to untrash.
	 */
	private function get_undelete_post_link( $post_id = null ) {

		if( null == $post_id ) {
			global $post;
			$post_id = $post->ID;
		}

		$_wpnonce = wp_create_nonce( 'untrash-post_' . $post_id );
		$url = admin_url( 'post.php?post=' . $post_id . '&action=untrash&_wpnonce=' . $_wpnonce );

		return $url;
	}


	/**
	 * Display the average review of a location.
	 *
	 * Used by a shortcode.
	 */
	public function location_rating( $atts ){
		ob_start();
		extract( shortcode_atts( array(
			'location' => '' ),
			$atts ) );

		$location = sanitize_title( $location );


		$reviews = get_posts(
			array(
				'post_type'   => 'business_review',
				'post_status' => 'publish',
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => 'br_review_info_location',
						'value'   => $location,
						'compare' => '=' ),
					array(
						'key'     => 'br_review_info_rating_avg',
						'value'   => 0,
						'compare' => '>' ) ) ) );

		$total_rating = $count = 0;
		foreach( $reviews as $review ){
			$total_rating += get_post_meta( $review->ID, 'br_review_info_rating_avg', true );
			$count++;
		}

		if( $count ) $grand_avg = $total_rating / $count;
		else $grand_avg = 0;

		$review_page_url = get_permalink( $this->config( 'review_page' ) );
		?>
		<div class="location_rating">
			<?php $this->show_stars( 'small', $grand_avg ); ?><br>

			<a class="rating-info" href="<?php echo $review_page_url . '#' . $location; ?>">
				<?php
				if( 0 == $grand_avg )
					_e( 'Be the first one to review.', 'business-review' );
				else
					echo sprintf(
						_n( '%0.2lf/5 from one rating.', '%0.2lf/5 from %d ratings.', $count, 'business-review' ),
						$grand_avg, $count );
				?>
			</a>
		</div>

		<?php
		return ob_get_clean();
	}
	
	
	/**
	 * Output content for the review input fields for the review submission form
	 */
	public function review_field( $atts ){
		extract( $atts );
		
		if( !isset( $atts['id'] ) ){
			$id = '';
		}
		else {
			unset( $atts['id'] );
		}		
		if( !isset( $atts['name'] ) ){
			$name = '';
		}
		else {
			unset( $atts['name'] );
		}
		
		if( !isset( $field ) && isset( $type ) ){
			return $this->create_field( $type, array(
				'id'   => $id,
				'name' => $name,
				'attr' => $atts ) );
		}
		elseif( is_numeric($field) ){
			$field = "field_$field";
		}
			

		return $this->create_field( $this->review_info[ $field ]['type'], array(
			'id'    => $id,
			'name'  => "review_info[$field]",
			'attr'  => array( 'class' => $class )
		), false );
		
	}
};

global $business_review;
$business_review = new Business_Review;
endif;
