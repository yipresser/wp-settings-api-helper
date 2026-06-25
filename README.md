# wp-settings-api-helper

An abstract class that simplifies the process of creating WordPress admin settings pages using the WordPress Settings API.

## Installation

You can install this package via Composer:

```bash
composer require yipresser/wp-settings-api-helper
```

Alternatively, you can use this class in your WordPress plugins or themes to easily define settings sections, fields, and handle their saving process.

## Usage

To use the helper, create a new class that extends `Yipresser\WpSettingsApiHelper\WP_Settings_API_Helper`.

### 1. Extend the Class and initialize the Settings

You need to define `$settings_options` for your database options and `$settings_sections` for the visual sections and fields you want to show on the page. These properties are `protected`, so set them inside your child class. Lastly, implement `sanitize_settings()` and run the `setup()` method.

```php
namespace MyPlugin\Admin;

use Yipresser\WpSettingsApiHelper\WP_Settings_API_Helper;

class My_Settings extends WP_Settings_API_Helper {

    public function __construct() {
        add_action( 'admin_init', [ $this, 'init' ] );
    }
      
    /**
     * Start the engine running.
     *
     * @return void
     */
    public function init() {
        // Define your options
        $this->settings_options = [
            [
                'option_group' => 'my_plugin_settings_group',
                'option_name'  => 'my_plugin_settings',
                // Optional args for register_setting():
                // 'args' => [ 'sanitize_callback' => [$this, 'sanitize_settings'] ]
            ]
        ];

        // Define your sections and fields
        $this->settings_sections = [
            [
                'id'          => 'my_plugin_general_section',
                'title'       => 'General Settings',
                'description' => 'These are the general settings for the plugin.',
                'menu_slug'   => 'my_plugin_slug', // Slug of the settings page
                'option_name' => 'my_plugin_settings',
                'fields'      => [
                    [
                        'type'    => 'text',
                        'title'   => 'API Key',
                        'id'      => 'api_key',
                        'name'    => 'api_key',
                        'default' => '',
                        'desc'    => 'Enter your API key here.',
                    ],
                    [
                        'type'    => 'checkbox',
                        'title'   => 'Enable Feature',
                        'id'      => 'enable_feature',
                        'name'    => 'enable_feature',
                        'label'   => 'Check to enable',
                        'default' => 0,
                    ]
                ]
            ]
        ];
        $this->setup();
    }

    /**
     * Sanitize settings before saving.
     *
     * @param array $option Saved options from the settings page.
     *
     * @return array
     */
    public function sanitize_settings( $option ) {
        $sanitized = [];

        if ( isset( $option['api_key'] ) ) {
            $sanitized['api_key'] = sanitize_text_field( $option['api_key'] );
        }

        $sanitized['enable_feature'] = ! empty( $option['enable_feature'] ) ? 1 : 0;

        return $sanitized;
    }
}
```

### 2. Display the Settings Form

When rendering your options page HTML, call the `render_settings_on_page()` method so it can generate the settings form, fields, and submit button.

```php
// Assuming this is inside your admin page callback function
public function my_plugin_options_page() {
    echo '<div class="wrap">';
    echo '<h1>My Plugin Options</h1>';
    
    // Pass the menu_slug used in your $settings_sections
    $my_settings->render_settings_on_page( 'my_plugin_slug' );
    
    echo '</div>';
}
```

## Supported Field Types

The `type` key in your field configuration supports the following values:

- `text`
- `url`
- `number` (supports `min`, `max`, and `step` attributes)
- `email`
- `password`
- `textarea` (supports optional `rows` and `cols`)
- `code-editor` (supports optional `code_type`, `code_theme`, `rows`, and `cols`)
- `select` (requires a `choices` array `['value' => 'Label']`; supports `multiple`)
- `radio` (requires a `choices` array)
- `checkbox` (optional `label` for text next to checkbox)
- `checkboxes` (requires a `choices` array)
- `slider-checkbox`
- `dropdown_pages`
- `color`
- `range` (supports `min`, `max`, and `step`, with a live output display)
- `image` (uses the WordPress media library; supports `image_return`)
- `hidden`
- `callback` (requires `callback` and optional `param` keys to render custom HTML)

Common field keys include:

- `id`: HTML ID and settings field ID.
- `name`: key inside the saved option array.
- `title`: settings field label.
- `default`: fallback value when no option is saved.
- `desc`: optional field description. Basic HTML is allowed.
- `class`: optional CSS class.
- `placeholder`: optional placeholder for text-like fields.
- `disabled`: disables the input when truthy.

### Example Select Field
```php
[
    'type'    => 'select',
    'title'   => 'Select Mode',
    'id'      => 'mode',
    'name'    => 'mode',
    'choices' => [
        'light' => 'Light Mode',
        'dark'  => 'Dark Mode'
    ],
    'default' => 'light'
]
```

### Example Multiple Select Field
```php
[
    'type'     => 'select',
    'title'    => 'Enabled Locations',
    'id'       => 'locations',
    'name'     => 'locations',
    'multiple' => true,
    'choices'  => [
        'header' => 'Header',
        'footer' => 'Footer',
    ],
    'default'  => [ 'header' ],
]
```

When `multiple` is enabled, the helper appends `[]` to the generated field name and expects the saved value to be an array.

### Example Color Field
```php
[
    'type'    => 'color',
    'title'   => 'Accent Color',
    'id'      => 'accent_color',
    'name'    => 'accent_color',
    'default' => '#2271b1',
]
```

### Example Range Field
```php
[
    'type'    => 'range',
    'title'   => 'Opacity',
    'id'      => 'opacity',
    'name'    => 'opacity',
    'min'     => 0,
    'max'     => 1,
    'step'    => 0.1,
    'default' => 0.8,
]
```

### Example Image Field
```php
[
    'type'         => 'image',
    'title'        => 'Logo',
    'id'           => 'logo',
    'name'         => 'logo',
    'image_return' => 'url', // Use 'id' to store the attachment ID instead.
]
```

Image fields automatically enqueue the WordPress media library when the settings are set up.

### Example Code Editor Field
```php
[
    'type'       => 'code-editor',
    'title'      => 'Custom CSS',
    'id'         => 'custom_css',
    'name'       => 'custom_css',
    'code_type'  => 'css',
    'code_theme' => 'dracula',
    'rows'       => 10,
    'cols'       => 80,
    'default'    => '',
]
```

`code_type` accepts shorthand values like `css`, `js`, `javascript`, `php`, `html`, `json`, `scss`, and `markdown`, or a full MIME-style value like `text/css` or `application/x-httpd-php`.

If you set `code_theme`, the helper only passes the theme name to CodeMirror. It does not enqueue the theme stylesheet for you. You can get the matching CodeMirror 5 theme CSS file from `https://github.com/codemirror/codemirror5/tree/master/theme`, add it to your plugin or theme, and enqueue it yourself:

```php
add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_style(
        'my-plugin-codemirror-theme',
        plugins_url( 'assets/css/codemirror/dracula.css', __FILE__ ),
        [ 'wp-codemirror' ],
        false
    );
} );
```

## Sanitization and Validation

`sanitize_settings($option)` is abstract and must be implemented in your child class. Use it to sanitize and validate all option values before they are saved to the database.

```php
public function sanitize_settings( $option ) {
    $sanitized = [];

    if ( isset( $option['api_key'] ) ) {
        $sanitized['api_key'] = sanitize_text_field( $option['api_key'] );
    }

    if ( isset( $option['logo'] ) ) {
        $sanitized['logo'] = esc_url_raw( $option['logo'] );
    }

    $sanitized['enable_feature'] = ! empty( $option['enable_feature'] ) ? 1 : 0;

    return $sanitized;
}
```

## Upgrading to 2.0.0

Version 2.0.0 includes breaking changes:

- `sanitize_settings()` is now abstract. Every child class must implement it.
- `$settings_options` and `$settings_sections` are now `protected`. Configure them inside your child class instead of reading or writing them from external code.

Composer constraints that only allow `^1.x` will need to be updated before installing 2.0.0.
