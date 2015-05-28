<?php
    /**
     * ReduxFramework Sample Config File
     * For full documentation, please visit: http://docs.reduxframework.com/
     */

    if ( ! class_exists( 'KWS_Business_Review_Config' ) ) {

        class KWS_Business_Review_Config {

            public $args = array();
            public $sections = array();
            public $theme;
            public $ReduxFramework;

            public function __construct() {

                if ( ! class_exists( 'ReduxFramework' ) ) {
                    return;
                }

                // This is needed. Bah WordPress bugs.  ;)
                add_action( 'plugins_loaded', array( $this, 'initSettings' ), 10 );


            }

            public function initSettings() {

                // Just for demo purposes. Not needed per say.
                $this->theme = wp_get_theme();

                // Set the default arguments
                $this->setArguments();

                // Set a few help tabs so you can see how it's done
                $this->setHelpTabs();

                // Create the sections and fields
                $this->setSections();

                if ( ! isset( $this->args['opt_name'] ) ) { // No errors please
                    return;
                }

                // If Redux is running as a plugin, this will remove the demo notice and links
                //add_action( 'redux/loaded', array( $this, 'remove_demo' ) );

                // Function to test the compiler hook and demo CSS output.
                // Above 10 is a priority, but 2 in necessary to include the dynamically generated CSS to be sent to the function.
                //add_filter('redux/options/'.$this->args['opt_name'].'/compiler', array( $this, 'compiler_action' ), 10, 3);

                // Change the arguments after they've been declared, but before the panel is created
                //add_filter('redux/options/'.$this->args['opt_name'].'/args', array( $this, 'change_arguments' ) );

                // Change the default value of a field after it's been set, but before it's been useds
                //add_filter('redux/options/'.$this->args['opt_name'].'/defaults', array( $this,'change_defaults' ) );

                // Dynamically add a section. Can be also used to modify sections/fields
                //add_filter('redux/options/' . $this->args['opt_name'] . '/sections', array($this, 'dynamic_section'));

                $this->ReduxFramework = new ReduxFramework( $this->sections, $this->args );
            }

            /**
             * This is a test function that will let you see when the compiler hook occurs.
             * It only runs if a field    set with compiler=>true is changed.
             * */
            function compiler_action( $options, $css, $changed_values ) {
                echo '<h1>The compiler hook has run!</h1>';
                echo "<pre>";
                print_r( $changed_values ); // Values that have changed since the last save
                echo "</pre>";
                //print_r($options); //Option values
                //print_r($css); // Compiler selector CSS values  compiler => array( CSS SELECTORS )

                /*
              // Demo of how to use the dynamic CSS and write your own static CSS file
              $filename = dirname(__FILE__) . '/style' . '.css';
              global $wp_filesystem;
              if( empty( $wp_filesystem ) ) {
                require_once( ABSPATH .'/wp-admin/includes/file.php' );
              WP_Filesystem();
              }

              if( $wp_filesystem ) {
                $wp_filesystem->put_contents(
                    $filename,
                    $css,
                    FS_CHMOD_FILE // predefined mode settings for WP files
                );
              }
             */
            }

            /**
             * Custom function for filtering the sections array. Good for child themes to override or add to the sections.
             * Simply include this function in the child themes functions.php file.
             * NOTE: the defined constants for URLs, and directories will NOT be available at this point in a child theme,
             * so you must use get_template_directory_uri() if you want to use any of the built in icons
             * */
            function dynamic_section( $sections ) {
                //$sections = array();
                $sections[] = array(
                    'title'  => __( 'Section via hook', 'business-review' ),
                    'desc'   => __( '<p class="description">This is a section created by adding a filter to the sections array. Can be used by child themes to add/remove sections from the options.</p>', 'business-review' ),
                    'icon'   => 'el el-paper-clip',
                    // Leave this as a blank section, no options just some intro text set above.
                    'fields' => array()
                );

                return $sections;
            }

            /**
             * Filter hook for filtering the args. Good for child themes to override or add to the args array. Can also be used in other functions.
             * */
            function change_arguments( $args ) {
                //$args['dev_mode'] = true;

                return $args;
            }

            /**
             * Filter hook for filtering the default value of any given field. Very useful in development mode.
             * */
            function change_defaults( $defaults ) {
                $defaults['str_replace'] = 'Testing filter hook!';

                return $defaults;
            }

            // Remove the demo link and the notice of integrated demo from the redux-framework plugin
            function remove_demo() {

                // Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
                if ( class_exists( 'ReduxFrameworkPlugin' ) ) {
                    remove_filter( 'plugin_row_meta', array(
                        ReduxFrameworkPlugin::instance(),
                        'plugin_metalinks'
                    ), null, 2 );

                    // Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
                    remove_action( 'admin_notices', array( ReduxFrameworkPlugin::instance(), 'admin_notices' ) );
                }
            }

            public function setSections() {

                /**
                 * Used within different fields. Simply examples. Search for ACTUAL DECLARATION for field examples
                 * */
                // Background Patterns Reader
                $sample_patterns_path = ReduxFramework::$_dir . '../sample/patterns/';
                $sample_patterns_url  = ReduxFramework::$_url . '../sample/patterns/';
                $sample_patterns      = array();

                if ( is_dir( $sample_patterns_path ) ) :

                    if ( $sample_patterns_dir = opendir( $sample_patterns_path ) ) :
                        $sample_patterns = array();

                        while ( ( $sample_patterns_file = readdir( $sample_patterns_dir ) ) !== false ) {

                            if ( stristr( $sample_patterns_file, '.png' ) !== false || stristr( $sample_patterns_file, '.jpg' ) !== false ) {
                                $name              = explode( '.', $sample_patterns_file );
                                $name              = str_replace( '.' . end( $name ), '', $sample_patterns_file );
                                $sample_patterns[] = array(
                                    'alt' => $name,
                                    'img' => $sample_patterns_url . $sample_patterns_file
                                );
                            }
                        }
                    endif;
                endif;

                ob_start();

                $ct          = wp_get_theme();
                $this->theme = $ct;
                $item_name   = $this->theme->get( 'Name' );
                $tags        = $this->theme->Tags;
                $screenshot  = $this->theme->get_screenshot();
                $class       = $screenshot ? 'has-screenshot' : '';

                $customize_title = sprintf( __( 'Customize &#8220;%s&#8221;', 'business-review' ), $this->theme->display( 'Name' ) );

                ?>
                <div id="current-theme" class="<?php echo esc_attr( $class ); ?>">
                    <?php if ( $screenshot ) : ?>
                        <?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
                            <a href="<?php echo wp_customize_url(); ?>" class="load-customize hide-if-no-customize"
                               title="<?php echo esc_attr( $customize_title ); ?>">
                                <img src="<?php echo esc_url( $screenshot ); ?>"
                                     alt="<?php esc_attr_e( 'Current theme preview', 'business-review' ); ?>"/>
                            </a>
                        <?php endif; ?>
                        <img class="hide-if-customize" src="<?php echo esc_url( $screenshot ); ?>"
                             alt="<?php esc_attr_e( 'Current theme preview', 'business-review' ); ?>"/>
                    <?php endif; ?>

                    <h4><?php echo $this->theme->display( 'Name' ); ?></h4>

                    <div>
                        <ul class="theme-info">
                            <li><?php printf( __( 'By %s', 'business-review' ), $this->theme->display( 'Author' ) ); ?></li>
                            <li><?php printf( __( 'Version %s', 'business-review' ), $this->theme->display( 'Version' ) ); ?></li>
                            <li><?php echo '<strong>' . __( 'Tags', 'business-review' ) . ':</strong> '; ?><?php printf( $this->theme->display( 'Tags' ) ); ?></li>
                        </ul>
                        <p class="theme-description"><?php echo $this->theme->display( 'Description' ); ?></p>
                        <?php
                            if ( $this->theme->parent() ) {
                                printf( ' <p class="howto">' . __( 'This <a href="%1$s">child theme</a> requires its parent theme, %2$s.', 'business-review' ) . '</p>', __( 'http://codex.wordpress.org/Child_Themes', 'business-review' ), $this->theme->parent()->display( 'Name' ) );
                            }
                        ?>

                    </div>
                </div>

                <?php
                $item_info = ob_get_contents();

                ob_end_clean();

                $sampleHTML = '';
                if ( file_exists( dirname( __FILE__ ) . '/info-html.html' ) ) {
                    Redux_Functions::initWpFilesystem();

                    global $wp_filesystem;

                    $sampleHTML = $wp_filesystem->get_contents( dirname( __FILE__ ) . '/info-html.html' );
                }
                $this->sections[] = array(
                    'title'  => __( 'Settings', 'business-review' ),
                    'icon'   => 'el el-cogs',
                    'fields' => array(
                        array(
                            'id'       => 'locations',
                            'type'     => 'multi_text',
                            'title'    => __( 'Locations', 'business-review' ),
                            'desc'     => __( 'Different locations where the business have branches.', 'business-review' )
                        ),
                        array(
                            'id'       => 'rating_fields',
                            'type'     => 'grid_input',
                            'title'    => __( 'Review fields', 'business-review' ),
                            'subtitle' => __( 'List all the fields or rating criterias here.', 'business-review' ) ),
                        array(
                            'id'       => 'satisfaction_threshold',
                            'type'     => 'text',
                            'title'    => __( 'Satisfaction Threshold', 'business-review' ),
                            'subtitle' => __( 'Minimum average rating to consider a review is positive.', 'business-review' ),
                            'default'  => '4'
                        ),
                        array(
                            'id'       => 'review_page',
                            'type'     => 'select',
                            'data'     => 'pages',
                            'title'    => __( 'Review Page', 'business-review' ),
                            'subtitle' => __( 'The page where you placed the [business_review] shortcode to display reviews.', 'business-review' ),
                            'default'  => 0
                        ),
                        array(
                            'id'       => 'number_of_reviews',
                            'type'     => 'text',
                            'title'    => __( 'Number of Reviews to Show', 'business-review'),
                            'default'  => 30
                        ),
                        array(
                            'id'       => 'review_columns',
                            'type'     => 'text',
                            'title'    => __( 'Review Columns', 'business-review' ),
                            'subtitle' => __( 'Number of columns to divide the reviews in.', 'business-review' ),
                            'default'  => 2
                        ),
                        array(
                            'id'        => 'show_gravatar',
                            'type'      => 'switch',
                            'title'     => __('Show Gravatar', 'business-review'),
                            'subtitle'  => __('Display the global avatar of the user.', 'business-review'),
                            'default'   => true,
                        ),
                        array(
                            'id'        => 'verify_poor_rating',
                            'type'      => 'switch',
                            'title'     => __('Verify Negative Reviews', 'business-review'),
                            'subtitle'  => __('Instead of directly publishing, keep reviews pending if it\'s negative.', 'business-review'),
                            'default'   => false,
                        ),
                        array(
                            'id'        => 'restriction_scope',
                            'type'      => 'select',
                            'title'     => __('Review Restriction Scope', 'business-review'),
                            'subtitle'  => __('Let user post only one review in the site or one review per location.', 'business-review'),
                            'options'   => array( 'location' => __( 'One review per location', 'business-review' ), 'website' => __( 'Review only ones regardless of what location choosen', 'business-review' ) ),
                            'default'   => 'location',
                        ),
                        array(
                            'id'        => 'one_review_per_ip',
                            'type'      => 'switch',
                            'title'     => __('One Review Per IP', 'business-review'),
                            'subtitle'  => __('Prevent multiple reviews from single IP.', 'business-review'),
                            'default'   => true,
                        ),
                        array(
                            'id'        => 'one_review_per_email',
                            'type'      => 'switch',
                            'title'     => __('One Review Per Email', 'business-review'),
                            'subtitle'  => __('Prevent multiple reviews from same email.', 'business-review'),
                            'default'   => true,
                        ),
                        array(
                            'id'       => 'review_form',
                            'type'     => 'editor',
                            'title'    => __( 'Review Form', 'business-review' ),
                            'subtitle' => __( 'The form that will be used to review', 'business-review' ),
                            'default'  => __( 'We are sorry to see that you were not satisfied with our service. Before publishing this review, we will contact you trying to fix your issue if possible.', 'business-review' )
                        ),
                        array(
                            'id'       => 'thank_you_message_positive',
                            'type'     => 'editor',
                            'title'    => __( 'Thank You Message for Positive Review', 'business-review' ),
                            'default'  => __( 'Thank you for your feedback. Your feedback inspires us making the service better.', 'business-review' )
                        ),
                        array(
                            'id'       => 'thank_you_message_negative',
                            'type'     => 'editor',
                            'title'    => __( 'Thank You Message for Negative Review', 'business-review' ),
                            'default'  => __( 'We are sorry to see that you were not satisfied with our service. Before publishing this review, we will contact you trying to fix your issue if possible.', 'business-review' )
                        ),
                        array(
                            'id'       => 'review_duplicate_error',
                            'type'     => 'editor',
                            'title'    => __( 'Already Reviewed Error Message', 'business-review' ),
                            'default'  => __( 'We only accept one review per person to protect the integrity.', 'business-review' )
                        ),
                        array(
                            'id'        => 'custom_css',
                            'type'      => 'ace_editor',
                            'title'     => __('Custom CSS Code', 'business-review'),
                            'subtitle'  => __('Paste your CSS code here.', 'business-review'),
                            'mode'      => 'css',
                            'theme'     => 'monokai',
                            'default'   => ''
                        )

                    )
                );

                $this->sections[] = array(
                    'type' => 'divide',
                );
            }

            public function setHelpTabs() {

                // Custom page help tabs, displayed using the help API. Tabs are shown in order of definition.
                // $this->args['help_tabs'][] = array(
                //     'id'      => 'redux-help-tab-1',
                //     'title'   => __( 'Theme Information 1', 'business-review' ),
                //     'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'business-review' )
                // );

                // $this->args['help_tabs'][] = array(
                //     'id'      => 'redux-help-tab-2',
                //     'title'   => __( 'Theme Information 2', 'business-review' ),
                //     'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'business-review' )
                // );

                // Set the help sidebar
                // $this->args['help_sidebar'] = __( '<p>This is the sidebar content, HTML is allowed.</p>', 'business-review' );
            }

            /**
             * All the possible arguments for Redux.
             * For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
             * */
            public function setArguments() {

                $theme = wp_get_theme(); // For use with some settings. Not necessary.

                $this->args = array(
                    'opt_name' => 'business_review_config',
                    'display_name' => __( 'Business Reviews Settings', 'business-review' ),
                    'display_version' => __('Business Reviews', 'business-review' ),
                    'page_slug' => 'business_reviews_options',
                    'page_title' => __( 'Business Reviews Settings', 'business-review' ),
                    'update_notice' => true,
                    'menu_type' => 'submenu',
                    'menu_title' => __( 'Settings', 'business-review' ),
                    'allow_sub_menu' => false,
                    'page_parent' => 'edit.php?post_type=business_review',
                    'page_parent_post_type' => 'business_review',
                    'customizer' => true,
                    'default_show' => true,
                    'default_mark' => '*',
                    'dev_mode' => false,
                    'admin_bar' => false,
                    'hints' => 
                    array(
                      'icon' => 'el-icon-info-sign',
                      'icon_position' => 'right',
                      'icon_size' => 'normal',
                      'tip_style' => 
                      array(
                        'color' => 'light',
                      ),
                      'tip_position' => 
                      array(
                        'my' => 'top left',
                        'at' => 'bottom right',
                      ),
                      'tip_effect' => 
                      array(
                        'show' => 
                        array(
                          'duration' => '500',
                          'event' => 'mouseover',
                        ),
                        'hide' => 
                        array(
                          'effect' => 'fade',
                          'duration' => '500',
                          'event' => 'mouseleave unfocus',
                        ),
                      ),
                    ),
                    'output' => true,
                    'output_tag' => true,
                    'compiler' => true,
                    'page_icon' => 'icon-themes',
                    'page_permissions' => 'manage_options',
                    'save_defaults' => true,
                    'show_import_export' => true,
                    'open_expanded' => true,
                    'transient_time' => '3600',
                    'network_sites' => true,
                  );

                // SOCIAL ICONS -> Setup custom links in the footer for quick links in your panel footer icons.
                // $this->args['share_icons'][] = array(
                //     'url'   => 'https://github.com/ReduxFramework/ReduxFramework',
                //     'title' => 'Visit us on GitHub',
                //     'icon'  => 'el-icon-github'
                //     //'img'   => '', // You can use icon OR img. IMG needs to be a full URL.
                // );
                // $this->args['share_icons'][] = array(
                //     'url'   => 'https://www.facebook.com/pages/Redux-Framework/243141545850368',
                //     'title' => 'Like us on Facebook',
                //     'icon'  => 'el-icon-facebook'
                // );
                // $this->args['share_icons'][] = array(
                //     'url'   => 'http://twitter.com/reduxframework',
                //     'title' => 'Follow us on Twitter',
                //     'icon'  => 'el-icon-twitter'
                // );
                // $this->args['share_icons'][] = array(
                //     'url'   => 'http://www.linkedin.com/company/redux-framework',
                //     'title' => 'Find us on LinkedIn',
                //     'icon'  => 'el-icon-linkedin'
                // );
            }

            public function validate_callback_function( $field, $value, $existing_value ) {
                $error = true;
                $value = 'just testing';

                /*
              do your validation

              if(something) {
                $value = $value;
              } elseif(something else) {
                $error = true;
                $value = $existing_value;
                
              }
             */

                $return['value'] = $value;
                $field['msg']    = 'your custom error message';
                if ( $error == true ) {
                    $return['error'] = $field;
                }

                return $return;
            }

            public static function class_field_callback( $field, $value ) {
                print_r( $field );
                echo '<br/>CLASS CALLBACK';
                print_r( $value );
            }

        }

        global $reduxConfig;
        $reduxConfig = new KWS_Business_Review_Config();
    } else {
        echo "The class named KWS_Business_Review_Config has already been called. <strong>Developers, you need to prefix this class with your company name or you'll run into problems!</strong>";
    }

    /**
     * Custom function for the callback referenced above
     */
    if ( ! function_exists( 'redux_my_custom_field' ) ):
        function redux_my_custom_field( $field, $value ) {
            print_r( $field );
            echo '<br/>';
            print_r( $value );
        }
    endif;

    /**
     * Custom function for the callback validation referenced above
     * */
    if ( ! function_exists( 'redux_validate_callback_function' ) ):
        function redux_validate_callback_function( $field, $value, $existing_value ) {
            $error = true;
            $value = 'just testing';

            /*
          do your validation

          if(something) {
            $value = $value;
          } elseif(something else) {
            $error = true;
            $value = $existing_value;
            
          }
         */

            $return['value'] = $value;
            $field['msg']    = 'your custom error message';
            if ( $error == true ) {
                $return['error'] = $field;
            }

            $return['warning'] = $field;

            return $return;
        }
    endif;
