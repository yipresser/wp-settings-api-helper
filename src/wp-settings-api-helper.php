<?php
/**
 * Yipresser WP Settings API Helper
 *
 * @package Yipresser\WpSettingsApiHelper
 */

namespace Yipresser\WpSettingsApiHelper;

/**
 * Yipresser WP Settings API Helper abstract class
 *
 * @version 2.0.2
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
	 *                              'option_value' => [],
	 *                              'default'=>[] //default values for the option
	 *                          ],
	 *                           ];
	 */
	protected $settings_options;


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
	 *  args => [
	 *    before_section,
	 *    after_section,
	 *    section_class,
	 *  ],
	 *  fields => [
	 *      type => (text|url|number|email|hidden|select|checkbox|checkboxes|slider-checkbox|radio|textarea|password|dropdown_pages|color|range|image|code-editor|callback),
	 *      title => 'Title for this field',
	 *      id => 'id attribute for this field',
	 *      name => 'input name attribute for this field',
	 *      value => '',
	 *      default => 'default value for this field',
	 *      min => 'int, for number input field only',
	 *      max => 'int, for number input field only',
	 *      step => 'int or float, for number input field only',
	 *      rows => 'int, for textarea and code-editor fields (default: 5)',
	 *      cols => 'int, for textarea and code-editor fields (default: 60)',
	 *      multiple => 'boolean, for select field only — promotes to <select multiple>',
	 *      image_return => 'url|id, for image field only — whether to store the attachment URL or ID (default: url)',
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
	 *      code_type => 'mime-like editor type for code-editor fields (css|text/javascript|application/x-httpd-php|text/html, etc)',
	 *      code_theme => 'CodeMirror theme slug for code-editor fields',
	 *      label_for => 'label for field, should be the same as id',
	 *      label => label text for checkbox,
	 *  ],
	 * ]]
	 */
	protected $settings_sections = [];

	/**
	 * Sections indexed by ID for O(1) lookup in render_section_description().
	 *
	 * @since 1.1.4
	 *
	 * @var array
	 */
	private $sections_by_id = [];

	/**
	 * Initial setup
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setup() {
		$this->sections_by_id = [];
		$option_values        = [];

		// first, register setting.
		if ( ! empty( $this->settings_options ) ) {
			foreach ( $this->settings_options as $option ) {
				if (
					isset( $option['option_name'] )
					&& array_key_exists( 'option_value', $option )
					&& is_array( $option['option_value'] )
				) {
					$option_values[ $option['option_name'] ] = $option['option_value'];
				}
				if ( ! isset( $option['args'] ) || ! is_array( $option['args'] ) ) {
					$option['args'] = [ 'sanitize_callback' => [ $this, 'sanitize_settings' ] ];
				}
				register_setting( $option['option_group'], $option['option_name'], $option['args'] );
			}
		}

		// then, register section.
		$needs_media = false;
		if ( ! empty( $this->settings_sections ) ) {
			foreach ( $this->settings_sections as $section ) {
				if ( isset( $section['id'] ) && isset( $section['title'] ) && isset( $section['menu_slug'] ) ) {
					$section['args']                        = $this->sanitize_section_args( isset( $section['args'] ) && is_array( $section['args'] ) ? $section['args'] : [] );
					$this->sections_by_id[ $section['id'] ] = $section;
					add_settings_section( $section['id'], $section['title'], [ $this, 'render_section_description' ], $section['menu_slug'], $section['args'] );
				}

				if ( ! empty( $section['fields'] ) && is_array( $section['fields'] ) ) {
					$option = array_key_exists( $section['option_name'], $option_values )
						? $option_values[ $section['option_name'] ]
						: get_option( $section['option_name'], [] );
					foreach ( $section['fields'] as $field ) {
						if ( ! isset( $field['id'] ) || ! isset( $field['title'] ) ) {
							continue;
						}
						if ( isset( $field['type'] ) && 'image' === $field['type'] ) {
							$needs_media = true;
						}
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
		if ( $needs_media ) {
			add_action(
				'admin_enqueue_scripts',
				function () {
					wp_enqueue_media();
				}
			);
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
		$section_id = isset( $args['id'] ) ? $args['id'] : '';
		$section    = isset( $this->sections_by_id[ $section_id ] ) ? $this->sections_by_id[ $section_id ] : null;
		if ( $section && isset( $section['description'] ) ) {
			echo '<p>' . wp_kses_post( $section['description'] ) . '</p>';
		}
	}

	/**
	 * Sanitize saved options. Must be implemented by child classes.
	 *
	 * @since 1.0.0.2
	 *
	 * @param array $option Saved options from Settings page.
	 *
	 * @return array
	 */
	abstract public function sanitize_settings( $option );


	/**
	 * Sanitize optional add_settings_section() wrapper args.
	 *
	 * @since 2.0.1
	 *
	 * @param array $args Section args.
	 *
	 * @return array
	 */
	private function sanitize_section_args( $args ) {
		$args = wp_parse_args(
			$args,
			[
				'before_section' => '',
				'after_section'  => '',
				'section_class'  => '',
			]
		);

		$args['before_section'] = is_scalar( $args['before_section'] ) ? wp_kses_post( (string) $args['before_section'] ) : '';
		$args['after_section']  = is_scalar( $args['after_section'] ) ? wp_kses_post( (string) $args['after_section'] ) : '';
		$args['section_class']  = is_scalar( $args['section_class'] ) ? $this->sanitize_html_class_list( (string) $args['section_class'] ) : '';

		return $args;
	}

	/**
	 * Sanitize a whitespace-separated HTML class list.
	 *
	 * @since 2.0.1
	 *
	 * @param string $class_list HTML class list.
	 *
	 * @return string
	 */
	private function sanitize_html_class_list( $class_list ) {
		$classes = preg_split( '/\s+/', $class_list );
		$classes = array_map( 'sanitize_html_class', $classes );
		$classes = array_filter( $classes, 'strlen' );

		return implode( ' ', array_unique( $classes ) );
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
			'id'           => '',
			'name'         => '',
			'placeholder'  => '',
			'value'        => '',
			'default'      => '',
			'class'        => '',
			'desc'         => '',
			'disabled'     => false,
			'min'          => '',
			'max'          => '',
			'step'         => '',
			'rows'         => 5,
			'cols'         => 60,
			'multiple'     => false,
			'image_return' => 'url',
			'label'        => '',
			'code_type'    => 'text/css',
			'code_theme'   => '',
		];
		// Explicit variable assignment instead of extract() to prevent scope injection.
		$parsed       = wp_parse_args( $args['field'], $defaults );
		$id           = isset( $parsed['id'] ) ? sanitize_key( $parsed['id'] ) : '';
		$name         = isset( $parsed['name'] ) ? sanitize_key( $parsed['name'] ) : '';
		$type         = isset( $parsed['type'] ) ? sanitize_key( $parsed['type'] ) : '';
		$placeholder  = isset( $parsed['placeholder'] ) ? $parsed['placeholder'] : '';
		$default      = isset( $parsed['default'] ) ? $parsed['default'] : '';
		$class        = isset( $parsed['class'] ) ? $parsed['class'] : '';
		$desc         = isset( $parsed['desc'] ) ? $parsed['desc'] : '';
		$disabled     = ! empty( $parsed['disabled'] );
		$min          = isset( $parsed['min'] ) ? $parsed['min'] : '';
		$max          = isset( $parsed['max'] ) ? $parsed['max'] : '';
		$step         = isset( $parsed['step'] ) ? $parsed['step'] : '';
		$rows         = ! empty( $parsed['rows'] ) ? absint( $parsed['rows'] ) : 5;
		$cols         = ! empty( $parsed['cols'] ) ? absint( $parsed['cols'] ) : 60;
		$multiple     = ! empty( $parsed['multiple'] );
		$image_return = isset( $parsed['image_return'] ) && 'id' === $parsed['image_return'] ? 'id' : 'url';
		$label        = isset( $parsed['label'] ) ? $parsed['label'] : '';
		$code_type    = isset( $parsed['code_type'] ) ? sanitize_text_field( $parsed['code_type'] ) : 'text/css';
		$code_theme   = isset( $parsed['code_theme'] ) ? sanitize_key( $parsed['code_theme'] ) : '';
		$option_name  = isset( $parsed['option_name'] ) ? $parsed['option_name'] : '';
		$option       = isset( $parsed['option'] ) && is_array( $parsed['option'] ) ? $parsed['option'] : [];
		$choices      = isset( $parsed['choices'] ) && is_array( $parsed['choices'] ) ? $parsed['choices'] : [];
		$callback     = isset( $parsed['callback'] ) ? $parsed['callback'] : null;
		$param        = isset( $parsed['param'] ) ? $parsed['param'] : null;
		$value        = array_key_exists( $name, $option ) ? $option[ $name ] : '';
		$disable_el   = $disabled ? ' disabled="disabled"' : '';

		switch ( $type ) {
			case 'text':
			case 'url':
			case 'email':
			case 'password':
				if ( 'password' !== $type && '' === $value && '' !== $default ) {
					$value = $default;
				}
				$this->render_text_input( $type, $option_name, $name, $id, $value, $placeholder, $class, $disable_el ); // phpcs:ignore -- $class is a local var, not the reserved keyword.
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'number':
				$min_attr  = '' !== $min ? ' min="' . esc_attr( (float) $min ) . '"' : '';
				$max_attr  = '' !== $max ? ' max="' . esc_attr( (float) $max ) . '"' : '';
				$step_attr = ! empty( $step ) ? ' step="' . esc_attr( $step ) . '"' : '';
				if ( '' === $value && '' !== $default ) {
					$value = $default;
				}
				echo '<input type="number" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text ' . esc_attr( $class ) . '"' . $min_attr . $max_attr . $step_attr . $disable_el . '/>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute fragments are built from escaped values and $disable_el is hardcoded.
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'textarea':
				if ( '' === $value && '' !== $default ) {
					$value = $default;
				}
				echo '<textarea name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" placeholder="' . esc_attr( $placeholder ) . '" rows="' . esc_attr( $rows ) . '" cols="' . esc_attr( $cols ) . '" class="' . esc_attr( $class ) . '"' . $disable_el . '>' . esc_html( $value ) . '</textarea>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $disable_el is a hardcoded safe string.
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'select':
				$multiple_attr = $multiple ? ' multiple="multiple"' : '';
				echo '<select name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']' . ( $multiple ? '[]' : '' ) . '" id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '"' . $multiple_attr . $disable_el . '>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $multiple_attr and $disable_el are hardcoded safe strings.
				foreach ( $choices as $cval => $clabel ) {
					if ( $multiple ) {
						$selected_values = ( '' === $value && is_array( $default ) ) ? $default : $value;
						$selected        = is_array( $selected_values ) && in_array( $cval, $selected_values, true ) ? ' selected="selected"' : '';
					} else {
						$selected = '' === $value ? selected( $cval, $default, false ) : selected( $cval, $value, false );
					}
					echo '<option value="' . esc_attr( $cval ) . '"' . $selected . '>' . esc_html( $clabel ) . '</option>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $selected is generated by selected() or a hardcoded string.
				}
				echo '</select>';
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'radio':
				foreach ( $choices as $cval => $clabel ) {
					$checked = '' === $value ? checked( $cval, $default, false ) : checked( $cval, $value, false );
					echo '<label><input type="radio" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '_' . esc_attr( $cval ) . '" value="' . esc_attr( $cval ) . '" class="' . esc_attr( $class ) . '" ' . $checked . $disable_el . ' /> ' . wp_kses_post( $clabel ) . '</label><br />';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $checked is generated by checked() and $disable_el is hardcoded.
				}
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'hidden':
				echo '<input type="hidden" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" />';
				break;
			case 'checkbox':
				$default = ! empty( $default ) ? absint( $default ) : 0;
				$value   = '' !== $value ? $value : $default;
				echo '<label><input type="checkbox" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="1" class="' . esc_attr( $class ) . '" ' . checked( 1, $value, false ) . $disable_el . ' /> ' . esc_html( $label ) . '</label>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $disable_el is a hardcoded safe string.
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'slider-checkbox': // CSS styling for .slider not included.
				$default              = ! empty( $default ) ? absint( $default ) : 0;
				$value                = '' !== $value ? $value : $default;
				$disable_slider_class = $disabled ? ' disabled' : '';
				echo '<label><input type="checkbox" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="1" class="' . esc_attr( $class ) . '" ' . checked( 1, $value, false ) . $disable_el . ' /><div class="slider' . esc_attr( $disable_slider_class ) . '"></div><span>' . wp_kses_post( $label ) . '</span></label>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $disable_el is a hardcoded safe string.
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'checkboxes':
				$cb_class        = ! empty( $class ) ? ' class="' . esc_attr( $class ) . '"' : '';
				$default_checked = ( ! empty( $default ) && is_array( $default ) )
					? array_map( 'sanitize_text_field', $default )
					: [];
				foreach ( $choices as $ckey => $cval ) {
					if ( array_key_exists( $name, $option ) && '' !== $option[ $name ] ) {
						$checked = ( is_array( $option[ $name ] ) && in_array( $ckey, $option[ $name ], true ) ) ? ' checked="checked"' : '';
					} else {
						$checked = in_array( $ckey, $default_checked, true ) ? ' checked="checked"' : '';
					}
					echo '<label><input type="checkbox" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . '][]" id="' . esc_attr( $id ) . '_' . esc_attr( $ckey ) . '" value="' . esc_attr( $ckey ) . '"' . $cb_class . $checked . ' /> ' . esc_html( $cval ) . '</label><br />';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute fragments are built from escaped values or hardcoded strings.
				}
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'dropdown_pages':
				$value = ! empty( $value ) ? $value : 0;
				wp_dropdown_pages(
					[
						'echo'              => 1,
						'name'              => esc_attr( $option_name . '[' . $name . ']' ),
						'id'                => esc_attr( $id ),
						'selected'          => esc_attr( $value ),
						'show_option_none'  => 'Choose a page',
						'option_none_value' => '-1',
					]
				);
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'color':
				if ( '' === $value && '' !== $default ) {
					$value = $default;
				}
				echo '<input type="color" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" class="' . esc_attr( $class ) . '"' . $disable_el . ' />';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $disable_el is a hardcoded safe string.
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'range':
				$min_attr  = '' !== $min ? ' min="' . esc_attr( (float) $min ) . '"' : '';
				$max_attr  = '' !== $max ? ' max="' . esc_attr( (float) $max ) . '"' : '';
				$step_attr = ! empty( $step ) ? ' step="' . esc_attr( $step ) . '"' : '';
				if ( '' === $value && '' !== $default ) {
					$value = $default;
				}
				echo '<input type="range" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" class="' . esc_attr( $class ) . '"' . $min_attr . $max_attr . $step_attr . $disable_el . ' />';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute fragments are built from escaped values and $disable_el is hardcoded.
				echo '<output for="' . esc_attr( $id ) . '">' . esc_html( $value ) . '</output>';
				$this->render_range_script( $id );
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'image':
				if ( '' === $value && '' !== $default ) {
					$value = $default;
				}
				$preview_url = '';
				if ( ! empty( $value ) ) {
					$preview_url = ( 'id' === $image_return && is_numeric( $value ) )
						? wp_get_attachment_image_url( (int) $value, 'thumbnail' )
						: $value;
				}
				echo '<div class="image-field' . ( ! empty( $class ) ? ' ' . esc_attr( $class ) : '' ) . '">';
				echo '<input type="hidden" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" />';
				echo '<div class="image-preview" style="margin-bottom:8px;">';
				if ( $preview_url ) {
					echo '<img src="' . esc_url( $preview_url ) . '" style="max-width:150px;max-height:150px;display:block;" />';
				}
				echo '</div>';
				echo '<button type="button" class="button image-select" id="' . esc_attr( $id ) . '_select" data-return="' . esc_attr( $image_return ) . '">' . esc_html__( 'Select Image' ) . '</button> ';
				echo '<button type="button" class="button image-remove" id="' . esc_attr( $id ) . '_remove"' . ( '' === $value ? ' style="display:none;"' : '' ) . '>' . esc_html__( 'Remove' ) . '</button>';
				echo '</div>';
				$this->render_image_script( $id, $image_return );
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'code-editor':
				if ( '' === $value && '' !== $default ) {
					$value = $default;
				}
				$code_type   = $this->normalize_code_editor_type( $code_type );
				$code_editor = wp_enqueue_code_editor(
					[
						'type'       => $code_type,
						'codemirror' => [
							'inputStyle'       => 'textarea',
							'matchBrackets'    => true,
							'lint'             => true,
							'direction'        => 'ltr',
							'colorpicker'      => [ 'mode' => 'edit' ],
							'foldOptions'      => [ 'widget' => '...' ],
							'continueComments' => true,
						],
					]
				);
				if ( false !== $code_editor ) {
					if ( ! empty( $code_theme ) ) {
						$code_editor['codemirror']['theme'] = $code_theme;
					}
					wp_add_inline_script(
						'wp-codemirror',
						sprintf(
							'jQuery( function() { wp.codeEditor.initialize( "' . esc_attr( $id ) . '", %s ); } );',
							wp_json_encode( $code_editor )
						)
					);
				}
				echo '<textarea name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" placeholder="' . esc_attr( $placeholder ) . '" rows="' . esc_attr( $rows ) . '" cols="' . esc_attr( $cols ) . '" class="' . esc_attr( $class ) . '"' . $disable_el . '>' . esc_html( $value ) . '</textarea>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $disable_el is a hardcoded safe string.
				if ( $desc ) {
					echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
				}
				break;
			case 'callback':
				// Only invoke if $callback is explicitly a valid, callable reference.
				if ( ! empty( $callback ) && is_callable( $callback ) ) {
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
	 * Render the range field live output script.
	 *
	 * @since 1.2.0
	 *
	 * @param string $id Field id attribute.
	 *
	 * @return void
	 */
	protected function render_range_script( $id ) {
		$script = sprintf(
			'(function(){var r=document.getElementById(%s);if(!r){return;}var o=r.nextElementSibling;if(!o){return;}r.addEventListener("input",function(){o.value=r.value;o.textContent=r.value;});})();',
			wp_json_encode( $id )
		);

		echo '<script>' . $script . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The only dynamic value is JSON encoded.
	}

	/**
	 * Render the image picker script for a single field.
	 *
	 * @since 1.2.0
	 *
	 * @param string $id           Field id attribute.
	 * @param string $image_return Whether to store the selected image URL or ID.
	 *
	 * @return void
	 */
	protected function render_image_script( $id, $image_return ) {
		$script = sprintf(
			"(function($){
				var fieldId=%s;
				var returnType=%s;
				var selectButton=$('#'+fieldId+'_select');
				var removeButton=$('#'+fieldId+'_remove');
				var wrap=selectButton.closest('.image-field');

				selectButton.on('click',function(){
					if('undefined'===typeof wp || !wp.media){return;}
					var frame=wp.media({title:%s,button:{text:%s},multiple:false});
					frame.on('select',function(){
						var att=frame.state().get('selection').first().toJSON();
						$('#'+fieldId).val('id'===returnType?att.id:att.url);
						var thumbUrl=(att.sizes&&att.sizes.thumbnail)?att.sizes.thumbnail.url:att.url;
						wrap.find('.image-preview').html($('<img>',{src:thumbUrl,style:'max-width:150px;max-height:150px;display:block;'}));
						removeButton.show();
					});
					frame.open();
				});

				removeButton.on('click',function(){
					$('#'+fieldId).val('');
					wrap.find('.image-preview').html('');
					removeButton.hide();
				});
			})(jQuery);",
			wp_json_encode( $id ),
			wp_json_encode( $image_return ),
			wp_json_encode( __( 'Select Image' ) ),
			wp_json_encode( __( 'Use this image' ) )
		);

		echo '<script>' . $script . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic values are JSON encoded.
	}

	/**
	 * Render a standard single-line text input.
	 *
	 * @since 1.1.4
	 *
	 * @param string $type        Input type attribute (text|url|email|password).
	 * @param string $option_name Option group name.
	 * @param string $name        Field name key.
	 * @param string $id          Field id attribute.
	 * @param string $value       Current field value.
	 * @param string $placeholder Placeholder text.
	 * @param string $css_class   Additional CSS classes.
	 * @param string $disable_el  Pre-built disabled attribute string or empty.
	 *
	 * @return void
	 */
	protected function render_text_input( $type, $option_name, $name, $id, $value, $placeholder, $css_class, $disable_el ) {
		echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $option_name ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text ' . esc_attr( $css_class ) . '"' . $disable_el . '/>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $disable_el is a hardcoded safe string.
	}

	/**
	 * Retrieve a single field value from a stored option array.
	 *
	 * @since 1.2.1
	 *
	 * @param string $option_name The option name as registered with register_setting().
	 * @param string $key         The field key within the option array.
	 * @param mixed  $default     Value to return if the key is not set.
	 *
	 * @return mixed
	 */
	public function get_option_value( $option_name, $key, $default = null ) {
		$option = get_option( $option_name, [] );
		return isset( $option[ $key ] ) ? $option[ $key ] : $default;
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
		if ( empty( $section ) ) {
			return;
		}

		// Default the id attribute to $section unless an id was specifically provided in $other_attributes.
		$id = $section;
		if ( isset( $other_attributes['id'] ) ) {
			$id = $other_attributes['id'];
			unset( $other_attributes['id'] );
		}

		// Strip reserved and control keys before building HTML attributes.
		$add_submit_btn = ! isset( $other_attributes['remove_submit_button'] ) || ! (bool) $other_attributes['remove_submit_button'];
		unset( $other_attributes['action'], $other_attributes['method'], $other_attributes['remove_submit_button'] );

		$attributes = '';
		foreach ( $other_attributes as $attribute => $value ) {
			$attributes .= sanitize_key( $attribute ) . '="' . esc_attr( $value ) . '" '; // Trailing space is important.
		}

		$id_attr = $id ? ' id="' . esc_attr( $id ) . '"' : '';
		$form    = '<form ' . $id_attr . $attributes . ' action="' . esc_url( admin_url( 'options.php' ), null, '&' ) . '" method="post">';
		echo $form; // phpcs:ignore
		settings_fields( $section );
		do_settings_sections( $section );
		if ( $add_submit_btn ) {
			submit_button( 'Save Changes' );
		}
		echo '</form>';
	}

	/**
	 * Normalize shorthand code editor types to WordPress-friendly MIME strings.
	 *
	 * @since 1.1.3
	 *
	 * @param string $code_type Requested code type.
	 *
	 * @return string
	 */
	protected function normalize_code_editor_type( $code_type ) {
		$map = [
			'css'        => 'text/css',
			'scss'       => 'text/x-scss',
			'sass'       => 'text/x-sass',
			'less'       => 'text/x-less',
			'javascript' => 'text/javascript',
			'js'         => 'text/javascript',
			'json'       => 'application/json',
			'html'       => 'text/html',
			'xml'        => 'application/xml',
			'markdown'   => 'text/x-markdown',
			'md'         => 'text/x-markdown',
			'php'        => 'application/x-httpd-php',
		];

		if ( isset( $map[ $code_type ] ) ) {
			return $map[ $code_type ];
		}

		if ( false !== strpos( $code_type, '/' ) ) {
			return $code_type;
		}

		return 'text/css';
	}
}
