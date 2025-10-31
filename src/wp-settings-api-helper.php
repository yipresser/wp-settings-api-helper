<?php
namespace Yipresser\WpSettingsApiHelper;

/**
 * Yipresser WP Settings API Helper abstract class
 *
 * @version 1.0.3.4
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
	 *                              'option_group',
	 *                              'option_name',
	 *                              'default'=>[] //default values for the option
	 *                          ],
	 *                           ];
	 */
	public $settings_options;


	/**
	 * This Setting Section array adds settings sections/fields to Admin option page
	 *
	 * @since    1.0.0
	 *
	 * @var    array
	 *
	 * @usage
	 * [[
	 *  id => 'ID for the section',
	 *  title => 'Title for the section,
	 *  description => 'Description for the section',
	 *  menu_slug => 'menu slug for registering section',
	 *  option_name => 'the name of the variable to be saved to the Options database,
	 *  fields => [
	 *      type => (text|number|email|hidden|select|checkbox|checkboxes|slider-checkbox|radio|textarea|password|dropdown_pages|file|callback),
	 *      title => 'Title for this field',
	 *      id => 'id attribute for this field',
	 *      name => 'input name attribute for this field',
	 *      value => '',
	 *      default => 'default value for this field',
	 *      min => 'int, for number input field only',
	 *      max => 'int, for number input field only',
	 *      disabled => 'boolean',
	 *      choices => '[
	 *            'slug' => 'Label',
	 *            'choice1' => 'Choice 1',
	 *         ], for radio, checkboxes only',
	 *      desc => 'Description for this field (optional)',
	 *      class => 'classname for this field (optional)',
	 *      placeholder => 'placeholder value for this field (optional)',
	 *      callback => 'function name, for "callback" type only',
	 *      param => 'additional parameter to pass to callback function',
	 *      label_for => 'label for field, should be the same as id',
	 *  ],
	 * ]]
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
		// first, register setting.
		if ( ! empty( $this->settings_options ) ) {
			foreach ( $this->settings_options as $option ) {
				if ( ! isset( $option['args'] ) || ! is_array( $option['args'] ) ) {
					$option['args'] = [ 'sanitize_callback' => [ $this, 'sanitize_settings' ] ];
				}
				register_setting( $option['option_group'], $option['option_name'], $option['args'] );
			}
		}

		// then, register section.
		if ( ! empty( $this->settings_sections ) ) {
			foreach ( $this->settings_sections as $section ) {
				if ( isset( $section['id'] ) && isset( $section['title'] ) && isset( $section['menu_slug'] ) ) {
					add_settings_section( $section['id'], $section['title'], [ $this, 'render_section_description' ], $section['menu_slug'] );
				}

				if ( ! empty( $section['fields'] ) && is_array( $section['fields'] ) ) {
					$option = get_option( $section['option_name'], [] );
					foreach ( $section['fields'] as $field ) {
						$field['option_name'] = $section['option_name'];
						$field['option']      = $option;
						$extra                = [ 'field' => $field ];
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
	 * @param array $args Fields array.
	 *
	 * @return void
	 */
	public function render_section_description( $args ) {
		if ( ! empty( $this->settings_sections ) ) {
			foreach ( $this->settings_sections as $section ) {
				if ( $section['id'] === $args['id'] ) {
					if ( isset( $section['description'] ) ) {
						echo '<p>' . $section['description'] . '</p>';
					}
					break;
				}
			}
		}
	}

	/**
	 *
	 * This is a placeholder function for sanitizing saved options.
	 *
	 * @param array $option Saved options from Settings page.
	 *
	 * @return array
	 *
	 * @since 1.0.0.2
	 */
	public function sanitize_settings( $option ) {
		return $option;
	}


	/**
	 * Render the settings fields
	 *
	 * @since 1.0.0
	 *
	 * @param array $args An array of all the fields settings.
	 *
	 * @return void
	 */
	public function render_field( $args ) {
		$defaults = [
			'id'          => '',
			'name'        => '',
			'placeholder' => '',
			'value'       => '',
			'default'     => '',
			'class'       => '',
			'desc'        => '',
			'disabled'    => false,
			'min'         => '',
			'max'         => '',
			'accepts'     => 'application/JSON',
		];
		extract( wp_parse_args( $args['field'], $defaults ) );  // phpcs:ignore
		$value = ! empty( $option[ $name ] ) ? $option[ $name ] : '';
		switch ( $type ) {
			case 'text':
				if ( empty( $value ) && ! empty( $default ) ) {
					$value = $default;
				}
				$disable_el = '';
				if ( $disabled ) {
					$disable_el = ' disabled="disabled"';
				}
				echo '<input type="text" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text ' . esc_attr( $class ) . '"' . $disable_el . '/>';
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'number':
				$min = ! empty( $min ) ? ' min="' . absint( $min ) . '"' : '';
				$max = ! empty( $max ) ? ' max="' . absint( $max ) . '"' : '';
				if ( empty( $value ) && ! empty( $default ) ) {
					$value = $default;
				}
				$disable_el = '';
				if ( $disabled ) {
					$disable_el = ' disabled="disabled"';
				}
				echo '<input type="number" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text ' . esc_attr( $class ) . '"' . $min . $max . $disable_el . '/>';
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'email':
				if ( empty( $value ) && ! empty( $default ) ) {
					$value = $default;
				}
				$disable_el = '';
				if ( $disabled ) {
					$disable_el = ' disabled="disabled"';
				}
				echo '<input type="email" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text ' . esc_attr( $class ) . '"' . $disable_el . '/>';
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'password':
				$disable_el = '';
				if ( $disabled ) {
					$disable_el = ' disabled="disabled"';
				}
				echo '<input type="password" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text ' . esc_attr( $class ) . '"' . $disable_el . '/>';
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'textarea':
				if ( empty( $value ) && ! empty( $default ) ) {
					$value = $default;
				}
				$disable_el = '';
				if ( $disabled ) {
					$disable_el = ' disabled="disabled"';
				}
				echo '<textarea name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" placeholder="' . esc_attr( $placeholder ) . '" rows="5" cols="60" class="' . esc_attr( $class ) . '"' . $disable_el . '>' . esc_html( stripslashes( $value ) ) . '</textarea>';
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'select':
				$disable_el = '';
				if ( $disabled ) {
					$disable_el = ' disabled="disabled"';
				}
				echo '<select name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '"' . $disable_el . '>';
				foreach ( $choices as $cval => $label ) {
					if ( empty( $value ) ) {
						$selected = selected( $cval, $default, false );
					} else {
						$selected = selected( $cval, $value, false );
					}
					echo '<option value="' . esc_attr( $cval ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'radio':
				$disable_el = '';
				if ( ! empty( $disabled ) ) {
					$disable_el = ' disabled="disabled"';
				}
				foreach ( $choices as $cval => $label ) {
					if ( empty( $value ) ) {
						$checked = checked( $cval, $default, false );
					} else {
						$checked = checked( $cval, $value, false );
					}
					echo '<label><input type="radio" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '_' . esc_attr( $cval ) . '" value="' . esc_attr( $cval ) . '" class="' . esc_attr( $class ) . '" ' . $checked . $disable_el . ' /> ' . wp_kses_post( $label ) . '</label><br />';
				}
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'hidden':
				echo '<input type="hidden" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( stripslashes( $value ) ) . '" />';
				break;
			case 'checkbox':
				$default    = ! empty( $default ) ? absint( $default ) : 0;
				$value      = ! empty( $value ) ? $value : $default;
				$disable_el = '';
				if ( ! empty( $disabled ) ) {
					$disable_el = ' disabled="disabled"';
				}
				echo '<label><input type="checkbox" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="1" class="' . esc_attr( $class ) . '" ' . checked( 1, $value, false ) . $disable_el . ' /> <span class="description">' . wp_kses_post( $desc ) . '</span></label>';
				break;
			case 'slider-checkbox': // include an additional div call slider. CSS styling not included.
				$default              = ! empty( $default ) ? absint( $default ) : 0;
				$value                = ! empty( $value ) ? $value : $default;
				$disable_el           = '';
				$disable_slider_class = '';
				if ( $disabled ) {
					$disable_el           = ' disabled="disabled"';
					$disable_slider_class = ' disabled';
				}
				echo '<label><input type="checkbox" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="1" class="' . esc_attr( $class ) . '" ' . checked( 1, $value, false ) . $disable_el . ' /><div class="slider' . esc_attr( $disable_slider_class ) . '"></div><p class="description">' . wp_kses_post( $desc ) . '</p></label>';
				break;
			case 'checkboxes':
				foreach ( $choices as $ckey => $cval ) {
					$cb_class = '';
					$checked  = '';
					if ( ! empty( $class ) ) {
						$cb_class = ' class="' . esc_attr( $class ) . '"';
					}
					if ( ! empty( $default ) && is_array( $default ) ) {
						$default = array_map( 'sanitize_text_field', $default );
					} else {
						$default = [];
					}
					if ( ! empty( $option[ $name ] ) ) {
						if ( is_array( $option[ $name ] ) && in_array( $ckey, $option[ $name ], true ) ) {
							$checked = ' checked="checked"';
						}
					} elseif ( in_array( $ckey, $default, true ) ) {
							$checked = ' checked="checked"';
					}
					echo '<label><input type="checkbox" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . '][]" id="' . esc_attr( $id ) . '_' . esc_attr( $ckey ) . '" value="' . esc_attr( $ckey ) . '"' . esc_attr( $cb_class ) . esc_attr( $checked ) . ' /> ' . esc_html( $cval ) . '</label><br />';
				}
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'dropdown_pages':
				$value = ! empty( $value ) ? $value : 0;
				echo wp_dropdown_pages(
					[
						'echo'              => 0,
						'name'              => $option_name . '[' . $name . ']',
						'id'                => $id,
						'selected'          => esc_attr( $value ),
						'show_option_none'  => 'Choose a page',
						'option_none_value' => '-1',
					]
				);
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'file':
				$disable_el = '';
				if ( $disabled ) {
					$disable_el = ' disabled="disabled"';
				}
				$accepts_el = '';
				if ( $accepts ) {
					$accepts_el = 'accept="'.$accepts . '"';
				}
				echo '<input type="file" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" class="regular-text ' . esc_attr( $class ) . '"' . $accepts_el . ' ' . $disable_el . '/>';
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'callback':
				if ( isset( $callback ) ) {
					if ( ! empty( $param ) ) {
						call_user_func( $callback, $args['field'], $param );
					} else {
						call_user_func( $callback, $args['field'] );
					}
				}
				break;
		}
	}

	/**
	 * Display settings on page
	 *
	 * @since 1.0.0
	 *
	 * @param string $section All the Settings sections and fields configuration.
	 *
	 * @param array  $other_attributes (Optional) Other attributes that should be output with the button, mapping attributes to their values, such as array( 'tabindex' => '1' ). These attributes will be output as attribute="value", such as tabindex="1".
	 *
	 * @return void
	 */
	public function render_settings_on_page( $section, $other_attributes = [] ) {
		if ( ! empty( $section ) ) {
			// Default the id attribute to $section unless an id was specifically provided in $other_attributes.
			$id = $section;
			if ( isset( $other_attributes['id'] ) ) {
				$id = $other_attributes['id'];
				unset( $other_attributes['id'] );
			}
			if ( isset( $other_attributes['action'] ) ) {
				unset( $other_attributes['action'] );
			}
			if ( isset( $other_attributes['method'] ) ) {
				unset( $other_attributes['method'] );
			}
			$attributes = '';
			if ( ! empty( $other_attributes ) ) {
				foreach ( $other_attributes as $attribute => $value ) {
					$attributes .= $attribute . '="' . esc_attr( $value ) . '" '; // Trailing space is important.
				}
			}

			$id_attr = $id ? ' id="' . esc_attr( $id ) . '"' : '';
			$form    = '<form ' . $id_attr . $attributes . ' action="' . esc_url( admin_url( 'options.php' ), null, '&' ) . '" method="post">';
			echo $form; // phpcs:ignore
			settings_fields( $section );
			do_settings_sections( $section );
			$add_submit_btn = true;
			if ( isset( $other_attributes['remove_submit_button'] ) && true === (bool) $other_attributes['remove_submit_button'] ) {
				$add_submit_btn = false;
			}
			if ( $add_submit_btn ) {
				submit_button( 'Save Changes' );
			}
			echo '</form>';
		}
	}
}
