<?php
/**
 * Redux Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Redux Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Redux Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     ReduxFramework
 * @author      Dovy Paukstys
 * @version     3.1.5
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

// Don't duplicate me!
if( !class_exists( 'ReduxFramework_grid_input' ) ) {

    /**
     * Main ReduxFramework_custom_field class
     *
     * @since       1.0.0
     */
    class ReduxFramework_grid_input extends ReduxFramework {
    
        /**
         * Field Constructor.
         *
         * Required - must call the parent constructor, then assign field and value to vars, and obviously call the render field function
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        function __construct( $field = array(), $value ='', $parent ) {
        
            
            $this->parent = $parent;
            $this->field = $field;
            $this->value = $value;

            if ( empty( $this->extension_dir ) ) {
                $this->extension_dir = trailingslashit( str_replace( '\\', '/', dirname( __FILE__ ) ) );
                $this->extension_url = site_url( str_replace( trailingslashit( str_replace( '\\', '/', ABSPATH ) ), '', $this->extension_dir ) );
            }    

            // Set default args for this field to avoid bad indexes. Change this to anything you use.
            $defaults = array(
                'options'           => array(),
                'stylesheet'        => '',
                'output'            => true,
                'enqueue'           => true,
                'enqueue_frontend'  => false
            );
            $this->field = wp_parse_args( $this->field, $defaults );            
        
        }

        /**
         * Field Render Function.
         *
         * Takes the vars and outputs the HTML for the field in the settings
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function render() {

            $this->add_text   = ( isset( $this->field['add_text'] ) ) ? $this->field['add_text'] : __( 'Add More', 'redux-framework' );
            $this->show_empty = ( isset( $this->field['show_empty'] ) ) ? $this->field['show_empty'] : true;

            echo '<div class="multi-input-holder redux-container-text" id="'.$this->field['id'].'">';
                
            if( isset( $this->value ) && is_array( $this->value ) ){
                foreach( $this->value as $k => $value ) {
                    $this->render_set( $k, $value );
                }
            } else {
                $this->render_set();
            }

            echo '</div>'; // .multi-input-holder
            
            echo '<a href="javascript:void(0);" class="add-input-set button-primary">' . $this->add_text . '</a><br/>';
        }
        
        
        /** 
         * Render each set of input
         * 
         * @param int $k index of the set, starting from 0
         * @param mixed[] $value array of values
         */
        private function render_set( $k = 0, $value = array( 'field_type' => '', 'name' => '', 'question' => '', 'rating_weight' => '', 'question_type' => '', 'dropdown_options' => '' ) ){
            echo "<fieldset class='input_set'>";
            echo "<h3>".__('ID', 'business-review').': <span class="id_holder">'.$k.'</span></h3>';
            
            echo "<div>";
            
            // Field Type
            echo "<div class='input_wrapper'>";
            echo "<label for='".$this->field['id']."-$k-field-type' id_format='".$this->field['id']."-%d-field-type'>".__('Field Type', 'business-review')."</label>";
            Business_Review::create_field( 'select', array(
                'name'    => $this->field['name'] . $this->field['name_suffix'] . "[$k][field_type]",
                'id'      => $this->field['id']."-$k-field-type",
                'value'   => $value['field_type'],
                'options' => array( 'rating' => __( 'Rating', 'business-review' ), 'question' => __( 'Question', 'business-review' ) ),
                'attr'    => array( 
                    'name_format' => $this->field['name'] . $this->field['name_suffix'] . "[%d][field_type]",
                    'id_format'   => $this->field['id']."-%d-field-type",
                    'class'       => 'wide-input field_type' ) ) );
            echo "</div>"; // .input-set

            // Name
            echo "<div class='input_wrapper'>";
            echo "<label for='".$this->field['id']."-$k-name' id_format='".$this->field['id']."-%d-name'>".__('Field Name', 'business-review')."</label>";
            Business_Review::create_field( 'text', array(
                'name'    => $this->field['name'] . $this->field['name_suffix'] . "[$k][name]",
                'id'      => $this->field['id']."-$k-name",
                'value'   => $value['name'],
                'attr'    => array( 
                    'name_format' => $this->field['name'] . $this->field['name_suffix'] . "[%d][name]",
                    'id_format'   => $this->field['id']."-%d-name",
                    'class'       => 'regular-text name' ) ) );
            echo "</div>"; // .input-set

            // Question
            echo "<div class='input_wrapper'>";
            echo "<label for='".$this->field['id']."-$k-question' id_format='".$this->field['id']."-%d-question'>".__('Question', 'business-review')."</label>";
            Business_Review::create_field( 'text', array(
                'name'    => $this->field['name'] . $this->field['name_suffix'] . "[$k][question]",
                'id'      => $this->field['id']."-$k-question",
                'value'   => $value['question'],
                'attr'    => array( 
                    'name_format' => $this->field['name'] . $this->field['name_suffix'] . "[%d][question]",
                    'id_format'   => $this->field['id']."-%d-question",
                    'class'       => 'regular-text question' ) ) );
            echo "</div>"; // .input-set
            
            // Rating Weight
            echo '<div class="field-type-rating input_wrapper">';
            echo "<label for='".$this->field['id']."-$k-rating-weight' id_format='".$this->field['id']."-%d-rating-weight'>".__('Rating Weight', 'business-review')."</label>";
            Business_Review::create_field( 'text', array(
                'name'    => $this->field['name'] . $this->field['name_suffix'] . "[$k][rating_weight]",
                'id'      => $this->field['id']."-$k-rating-weight",
                'value'   => $value['rating_weight'],
                'attr'    => array( 
                    'name_format' => $this->field['name'] . $this->field['name_suffix'] . "[%d][rating_weight]",
                    'id_format'   => $this->field['id']."-%d-rating-weight",
                    'class'       => 'regular-text rating_weight' ) ) );
            echo '</div>'; // /field-type-rating
            
            echo '<div class="field-type-question">';
            // Question type
            echo '<div class="input_wrapper">';
            echo "<label for='".$this->field['id']."-$k-question-type' id_format='".$this->field['id']."-%d-question-type'>".__('Question Type', 'business-review')."</label>";
            Business_Review::create_field( 'select', array(
                'name'    => $this->field['name'] . $this->field['name_suffix'] . "[$k][question_type]",
                'id'      => $this->field['id']."-$k-field-type",
                'value'   => $value['question_type'],
                'options' => array(
                    'text'     => __( 'Text', 'business-review' ),
                    'select'   => __( 'Dropdown', 'business-review' ),
                    'email'    => __( 'Email', 'business-review' ),
                    'phone'    => __( 'Phone', 'business-review' ),
                    'textarea' => __( 'Textarea', 'business-review' ),
                    'date'     => __( 'Date', 'business-review' ) ),
                'attr'    => array( 
                    'name_format' => $this->field['name'] . $this->field['name_suffix'] . "[%d][question_type]",
                    'id_format'   => $this->field['id']."-%d-field-type",
                    'class'       => 'wide-input question_type' ) ) );
            echo '</div>'; // .input_wrapper
            
            // Dropdown options
            echo '<div class="question-type-select input_wrapper ul_height">';
            echo "<label for='".$this->field['id']."-$k-dropdown-options' id_format='".$this->field['id']."-%d-dropdown-options'>".__('Dropdown Options (One per line)', 'business-review')."</label>";
            Business_Review::create_field( 'textarea', array(
                'name'    => $this->field['name'] . $this->field['name_suffix'] . "[$k][dropdown_options]",
                'id'      => $this->field['id']."-$k-dropdown-options",
                'value'   => $value['dropdown_options'],
                'attr'    => array( 
                    'name_format' => $this->field['name'] . $this->field['name_suffix'] . "[%d][dropdown_options]",
                    'id_format'   => $this->field['id']."-%d-dropdown-options",
                    'class'       => 'wide-input dropdown_options' ) ) );
            echo '</div>'; // .question-type-select
            
            echo '<div style="clear:both"></div>';
            
            echo '</div>'; // .field-type-question
            
            echo '<div class="input_wrapper">';
            echo '<br/><a href="javascript:void(0);" class="remove-input-set button-secondary">' . __('Remove', 'business-review') . '</a>';
            echo '</div>'; // .input_wrapper
            
            echo "</div>";
            echo "</fieldset>";
        }
        
    
        /**
         * Enqueue Function.
         *
         * If this field requires any scripts, or css define this function and register/enqueue the scripts/css
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function enqueue() {

            $extension = ReduxFramework_extension_grid_input::getInstance();
        
            wp_enqueue_script(
                'jquery-add-input-area', 
                $this->extension_url . 'jquery.add-input-area.js', 
                array( 'jquery' ),
                time(),
                true
            );
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-accordion');
            wp_enqueue_script(
                'grid-input', 
                $this->extension_url . 'field_grid_input.js', 
                array( 'jquery', 'jquery-ui-accordion', 'jquery-ui-core' ),
                time(),
                true
            );

            wp_enqueue_style(
                'grid-input', 
                $this->extension_url . 'field_grid_input.css',
                time(),
                true
            );
        
        }  
        
    }
}