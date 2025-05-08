<?php
namespace Yipresser\WpSettingsApiHelper;

/**
 * Yipresser WP Settings API Helper abstract class
 *
 * @version 1.0.1.3
 *
 * @author Damien Oh <damien@yipresser.com>
 */

abstract class WP_Settings_API_Helper {

    /**
     * Option name to store data in the database
     *
     * @since    1.0.0
     *
     * @var    array
     *
     * @usage $settings_options = [[
     * 								'option_group',
     *                              'option_name',
     * 								'default'=>[] //default values for the option
     * 							],
     * 							 ];
     */
    public $settings_options;


    /**
     * This Setting Section array adds settings sections/fields to Admin option page
     *
     * @since    1.0.0
     *
     * @var    array
     *
     * @usage    [[
     * 				id,
     *              title,
     *              description,
     *              menu_slug,
     *              option_name,
     *              fields => [
     *                          type => (text|select|checkbox|checkboxes|radio|textarea|password|callback),
     *                          title =>'',
     *                          id =>'',
     *                          name => '',
     *                          value => '',
     *                          default => '',
     *                          min => '',
     *                          max => '',
     * 						    disabled => '',
     *                          choices => [],
     *                          desc (optional),
     *                          class (optional),
     *                          placeholder (optional),
     *                          callback (only if type == function),
     *                          param (only if type == function),
     *                          label_for => '',
     *                         ],
     *            ]
     *        ]
     *
     */
    public $settings_sections = [];

	/**
	 * Initial setup
     *
     * @since 1.0.0
     *
     * @return void
	 */
    public function setup() {
        // first, register setting
        if ( ! empty( $this->settings_options ) ) {
            foreach ( $this->settings_options as $option ) {
                if ( ! isset( $option['args'] ) || ! is_array($option['args']) ) {
                    $option['args'] = [ 'sanitize_callback' => [$this, 'sanitize_settings'] ];
                }
                register_setting( $option['option_group'], $option['option_name'], $option['args'] );
            }
        }

        //register section
        if ( ! empty( $this->settings_sections ) ) {
            foreach ( $this->settings_sections as $section ) {
                if ( isset( $section['id']) && isset( $section['title']) && isset( $section['menu_slug'])) {
                    add_settings_section( $section['id'], $section['title'], [ $this, 'render_section_description' ], $section['menu_slug'] );
                }

                if ( ! empty( $section['fields'] ) && is_array( $section['fields'] ) ) {
                    $option = get_option($section['option_name']);
                    foreach ( $section['fields'] as $field ) {
                        $field['option_name'] = $section['option_name'];
                        $field['option'] = $option;
                        $extra = [ 'field' => $field ];
                        if ( isset( $field['label_for'] ) ) {
                            $extra['label_for'] = $field['label_for'];
                        }
                        add_settings_field( $field['id'], $field['title'], [ $this, 'render_field' ], $section['menu_slug'], $section['id'], $extra );
                    }
                }
            }
        }
    }

	/**
	 * Render the section description
     *
     * @since 1.0.0
     *
     * @param $args
	 *
     * @return void
	 */
    public function render_section_description( $args ) {
        if ( ! empty( $this->settings_sections ) ) {
            foreach ( $this->settings_sections as $section ) {
                if ( $section['id'] == $args['id'] ) {
                    echo '<p>' . $section['description'] . '</p>';
                    break;
                }
            }
        }
    }

	/**
	 *
     * This is a placeholder function for sanitizing saved options.
     *
     * @since 1.0.0.2
     *
     * @param $option
	 *
     * @return mixed
	 */
    public function sanitize_settings( $option ) {
        return $option;
    }


	/**
	 * Render the settings fields
     *
     * @since 1.0.0
     *
     * @param $args
     *
	 * @return void
	 */
    public function render_field( $args ) {
        $defaults = [ 'id' => '', 'name' => '', 'placeholder' => '', 'value' => '', 'default' => '', 'class' => '', 'desc' => '' ];
        extract( wp_parse_args( $args['field'], $defaults ) );
        $value = ! empty($option[$name]) ? $option[$name] : '';
        switch ( $type ) {
            case 'text':
                if ( empty($value) && !empty($default) ) {
                    $value = $default;
                }
                $disable_el = '';
                if ($disabled) {
                    $disable_el = ' disabled="disabled"';
                }
                echo '<input type="text" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' .
                     esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" placeholder="' . esc_attr(
                             $placeholder
                    ) . '" class="regular-text ' . esc_attr( $class ) . '"' . $disable_el . '/>';
                if ( $desc ) {
                    echo '<p class="description">' . esc_html( $desc ) . '</p>';
                }
                break;
            case 'number':
                $min = ! empty( $min ) ? ' min="'.absint($min).'"' : '';
                $max = ! empty( $max ) ? ' max="'.absint($max).'"' : '';
                $disable_el = '';
                if ($disabled) {
                    $disable_el = ' disabled="disabled"';
                }
                echo '<input type="number" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' .
                     esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" placeholder="' . esc_attr(
                             $placeholder
                    ) . '" class="regular-text '. esc_attr( $class ) . '"' . $min . $max . $disable_el .'/>';
                if ( $desc ) {
                    echo '<p class="description">' . esc_html( $desc ) . '</p>';
                }
                break;
            case 'email':
                if ( empty($value) && !empty($default) ) {
                    $value = $default;
                }
                $disable_el = '';
                if ($disabled) {
                    $disable_el = ' disabled="disabled"';
                }
                echo '<input type="email" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' .
                     esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" placeholder="' . esc_attr(
                             $placeholder
                    ) . '" class="regular-text '. esc_attr( $class ) . '"' . $disable_el . '/>';
                if ( $desc ) {
                    echo '<p class="description">' . esc_html( $desc ) . '</p>';
                }
                break;
            case 'password':
                $disable_el = '';
                if ($disabled) {
                    $disable_el = ' disabled="disabled"';
                }
                echo '<input type="password" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="'
                     . esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" placeholder="' .
                     esc_attr(
                             $placeholder ) . '" class="regular-text ' . esc_attr( $class ) . '"' . $disable_el . '/>';
                if ( $desc ) {
                    echo '<p class="description">' . esc_html( $desc ) . '</p>';
                }
                break;
            case 'textarea':
                if ( empty($value) && !empty($default) ) {
                    $value = $default;
                }
                $disable_el = '';
                if ($disabled) {
                    $disable_el = ' disabled="disabled"';
                }
                echo '<textarea name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' .
                     esc_attr( $id ) . '" placeholder="' . esc_attr( $placeholder ) . '" rows="5" cols="60" class="'
                     . esc_attr( $class ) . '"' . $disable_el . '>' . esc_html( stripslashes( $value ) ) . '</textarea>';
                if ( $desc ) {
                    echo '<p class="description">' . esc_html( $desc ) . '</p>';
                }
                break;
            case 'select':
                $disable_el = '';
                if ($disabled) {
                    $disable_el = ' disabled="disabled"';
                }
                echo '<select name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr(
                        $id ) . '" class="' . esc_attr( $class ). '"'. $disable_el .'>';
                foreach ( $choices as $cval => $label ) {
                    if ( empty($value)) {
                        $selected = selected( $cval, $default, false );
                    } else {
                        $selected = selected( $cval, $value, false );
                    }
                    echo '<option value="' . esc_attr( $cval ). '" ' . $selected . '>' .
                         esc_html( $label ) . '</option>';
                }
                echo '</select>';
                if ( $desc ) {
                    echo '<p class="description">' . esc_html( $desc ) . '</p>';
                }
                break;
            case 'radio':
                foreach ( $choices as $cval => $label ) {
                    if ( empty($value)) {
                        $checked = checked( $cval, $default, false );
                    } else {
                        $checked = checked( $cval, $value, false );
                    }
                    echo '<label><input type="radio" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '_' . esc_attr( $cval ) . '" value="' . esc_attr( $cval ) . '" class="' . esc_attr( $class ) . '" ' . $checked . ' /> ' . esc_html( $label ) . '</label><br />';
                }
                if ( $desc ) {
                    echo '<p class="description">' . esc_html( $desc ) . '</p>';
                }
                break;
            case 'hidden':
                echo '<input type="hidden" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' .
                     esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" />';
                break;
            case 'checkbox':
                $default = ! empty( $default ) ? absint($default) : 0;
                $value = ! empty( $value ) ? $value : $default;
                $disable_el = '';
                if ($disabled) {
                    $disable_el = ' disabled="disabled"';
                }
                echo '<label><input type="checkbox" name="' . esc_attr( $option_name ) . '[' . esc_attr($name ) . ']" id="' . esc_attr( $id ) . '" value="1" class="' . esc_attr(  $class ) . '" ' . checked( 1, $value, false ) . $disable_el . ' /> ' . esc_html( $desc ) . '</label>';
                break;
            case 'checkboxes':
                foreach ( $choices as $ckey => $cval ) {
                    $cb_class = $checked = '';
                    if ( !empty( $class) ) {
                        $cb_class = ' class="' . esc_attr($class) . '"';
                    }
                    if ( ! empty($default) && is_array($default ) ) {
                        $default = array_map( 'sanitize_text_field', $default );
                    } else {
                        $default = [];
                    }
                    if ( !empty($option[$name])) {
                        if (is_array($option[$name]) && in_array($ckey, $option[$name])) {
                            $checked = ' checked="checked"';
                        }
                    } else {
                        if (in_array($ckey, $default) ) {
                            $checked = ' checked="checked"';
                        }
                    }
                    echo '<label><input type="checkbox" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) .
                         '][]" id="' . esc_attr( $id ) . '_' . esc_attr( $ckey ) . '" value="' . esc_attr( $ckey ) . '"' . esc_attr( $cb_class ) . esc_attr( $checked ) . ' /> ' . esc_html( $cval ) . '</label><br />';
                }
                if ( $desc ) {
                    echo '<p class="description">' . esc_html( $desc ) . '</p>';
                }
                break;
            case 'dropdown_pages':
	            $value = ! empty( $value ) ? $value : 0;
                echo wp_dropdown_pages( [ 'echo' => 0, 'name' => $option_name . '[' . $name . ']', 'id' => $id, 'selected' => esc_attr( $value ), 'show_option_none' => 'Choose a page', 'option_none_value' => '-1' ] );
                if ( $desc ) {
                    echo '<p class="description">' . esc_html( $desc ) . '</p>';
                }
                break;
            case 'callback':
                if ( isset( $callback ) ){
	                if(!empty($param))
		                call_user_func($callback,$args['field'], $param);
	                else
		                call_user_func($callback, $args['field']);
                }
                break;
        }
    }

	/**
	 * Display settings on page
     *
     * @since 1.0.0
     *
     * @param $section
	 *
     * @return void
	 */
    public function render_settings_on_page($section) {
        if ( ! empty( $section ) ) : ?>
            <form action="<?php echo esc_url(admin_url( 'options.php' ), null, '&'); ?>" method="post">
            <?php
                settings_fields( $section );
                do_settings_sections( $section );
                submit_button( 'Save Changes' ); ?>
            </form>
        <?php
        endif;
    }
}